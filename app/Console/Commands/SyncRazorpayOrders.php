<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\PaymentLog;
use App\Services\RazorpayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncRazorpayOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sync-razorpay 
                            {--order= : Specific Order ID or order_number to sync}
                            {--minutes=10 : Only check orders created at least X minutes ago}
                            {--days=7 : Only check orders created within the last X days}
                            {--limit=50 : Maximum number of orders to process per run}
                            {--dry-run : Check status without updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile pending Razorpay orders by checking payment status via Razorpay API';

    /**
     * Execute the console command.
     */
    public function handle(RazorpayService $razorpayService): int
    {
        $this->info('[' . now()->toDateTimeString() . '] Starting Razorpay payment synchronization...');

        $orderOption = $this->option('order');
        $minutes = (int) $this->option('minutes');
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY-RUN mode enabled: No database records will be modified.');
        }

        $query = Order::query()
            ->where('payment_method', 'razorpay')
            ->whereIn('payment_status', ['payment_pending', 'created'])
            ->whereNotNull('razorpay_order_id')
            ->where('razorpay_order_id', '!=', '');

        if ($orderOption) {
            $query->where(function ($q) use ($orderOption) {
                $q->where('id', $orderOption)
                  ->orWhere('order_number', $orderOption)
                  ->orWhere('razorpay_order_id', $orderOption);
            });
        } else {
            // Apply safe time boundaries so we don't interfere with customers currently typing OTP
            if ($minutes > 0) {
                $query->where('created_at', '<=', Carbon::now()->subMinutes($minutes));
            }
            if ($days > 0) {
                $query->where('created_at', '>=', Carbon::now()->subDays($days));
            }
            $query->limit($limit);
        }

        $orders = $query->orderBy('id', 'asc')->get();

        if ($orders->isEmpty()) {
            $this->info('No pending Razorpay orders found matching criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$orders->count()} pending Razorpay order(s) to verify.");

        $recoveredCount = 0;
        $unpaidCount = 0;
        $failedCount = 0;
        $errorCount = 0;

        foreach ($orders as $order) {
            $this->line("Checking Order #{$order->order_number} (ID: {$order->id}, Razorpay Order ID: {$order->razorpay_order_id})...");

            try {
                // Fetch payments associated with this Razorpay Order ID
                $payments = $razorpayService->fetchOrderPayments($order->razorpay_order_id);
                $paymentItems = $payments['items'] ?? [];

                if (empty($paymentItems)) {
                    // If order is older than 60 minutes and customer never even attempted payment, mark as cancelled/abandoned
                    $isExpired = $order->created_at && $order->created_at->lte(Carbon::now()->subMinutes(60));
                    if ($isExpired && !$isDryRun) {
                        $this->comment("  -> No payments attempted after 60+ minutes. Marking order as payment_cancelled.");
                        DB::transaction(function () use ($order) {
                            $order->update([
                                'payment_status' => 'payment_cancelled',
                                'status' => 'payment_cancelled',
                                'cancelled_by' => 'customer',
                                'cancellation_reason' => 'Payment checkout abandoned / session expired (no payment attempted)',
                            ]);
                        });
                    } else {
                        $this->comment("  -> No payments initiated yet on Razorpay.");
                    }
                    $unpaidCount++;
                    continue;
                }

                $capturedPayment = null;
                $failedPayment = null;

                foreach ($paymentItems as $payment) {
                    $status = $payment['status'] ?? '';
                    if ($status === 'captured') {
                        $capturedPayment = $payment;
                        break;
                    } elseif (in_array($status, ['failed', 'cancelled'])) {
                        $failedPayment = $payment;
                    }
                }

                if ($capturedPayment) {
                    $paymentId = $capturedPayment['id'];
                    $amount = isset($capturedPayment['amount']) ? ($capturedPayment['amount'] / 100) : $order->total_amount;

                    $this->info("  [CAPTURED] Found captured payment: {$paymentId} for ₹{$amount}");

                    if ($isDryRun) {
                        $this->warn("  -> [DRY-RUN] Would mark Order #{$order->order_number} as PAID.");
                        $recoveredCount++;
                    } else {
                        $payloadArray = is_object($capturedPayment) && method_exists($capturedPayment, 'toArray')
                            ? $capturedPayment->toArray()
                            : (array)$capturedPayment;

                        $result = $razorpayService->markOrderAsPaid($order, $paymentId, 'cron_sync', $payloadArray);

                        if ($result['success']) {
                            $this->info("  -> SUCCESS: Order #{$order->order_number} marked as PAID and PROCESSING.");
                            $recoveredCount++;
                        } else {
                            $this->warn("  -> Notice: " . ($result['message'] ?? 'Not updated'));
                        }
                    }
                } elseif ($failedPayment) {
                    $failDesc = $failedPayment['error_description'] ?? ($failedPayment['error_reason'] ?? 'Payment failed/declined on Razorpay');
                    $this->line("  -> Payment status on Razorpay is 'failed': {$failDesc}");
                    $failedCount++;

                    if ($isDryRun) {
                        $this->warn("  -> [DRY-RUN] Would mark Order #{$order->order_number} as PAYMENT_CANCELLED.");
                    } else {
                        // Mark order as payment_cancelled so it is NOT checked again in subsequent cron runs
                        DB::transaction(function () use ($order, $failDesc) {
                            $order->update([
                                'payment_status' => 'payment_cancelled',
                                'status' => 'payment_cancelled',
                                'cancelled_by' => 'payment_failed',
                                'cancellation_reason' => 'Payment failed on Razorpay: ' . $failDesc,
                            ]);
                        });

                        // Avoid logging duplicate failed entries in payment_logs for the same payment ID
                        $paymentId = $failedPayment['id'] ?? null;
                        $alreadyLogged = $paymentId ? PaymentLog::where('order_id', $order->id)
                            ->where('razorpay_payment_id', $paymentId)
                            ->where('status', 'failed')
                            ->exists() : false;

                        if (!$alreadyLogged) {
                            $razorpayService->logEvent([
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'event_type' => 'cron_sync',
                                'status' => 'failed',
                                'razorpay_order_id' => $order->razorpay_order_id,
                                'razorpay_payment_id' => $paymentId,
                                'amount' => $order->total_amount,
                                'message' => 'Payment failed on Razorpay: ' . $failDesc,
                                'payload' => (is_object($failedPayment) && method_exists($failedPayment, 'toArray')) ? $failedPayment->toArray() : (array)$failedPayment,
                            ]);
                        }
                    }
                } else {
                    $this->line("  -> Payment exists but not captured yet (authorized / created).");
                    $unpaidCount++;
                }

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  -> API Error for Order #{$order->order_number}: " . $e->getMessage());

                Log::error("Razorpay sync cron error for Order #{$order->order_number}: " . $e->getMessage(), [
                    'order_id' => $order->id,
                    'razorpay_order_id' => $order->razorpay_order_id,
                ]);

                $razorpayService->logEvent([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'event_type' => 'cron_sync',
                    'status' => 'error',
                    'razorpay_order_id' => $order->razorpay_order_id,
                    'message' => 'API Error during cron sync: ' . $e->getMessage(),
                ]);
            }
        }

        $summaryMessage = "Razorpay sync completed: {$recoveredCount} recovered, {$unpaidCount} unpaid, {$failedCount} failed, {$errorCount} errors.";
        $this->info($summaryMessage);
        Log::info($summaryMessage);

        return Command::SUCCESS;
    }
}
