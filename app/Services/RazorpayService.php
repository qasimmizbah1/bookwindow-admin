<?php

namespace App\Services;

use Razorpay\Api\Api;
use App\Models\Order;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Vendor;
use App\Models\PaymentLog;
use App\Mail\OrderConfirmation;
use App\Mail\AdminOrderNotification;
use App\Mail\VendorOrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RazorpayService
{
    protected $razorpay;
    protected ?string $key;
    protected ?string $secret;
    protected ?string $webhookSecret;

    public function __construct()
    {
        $this->key = config('services.razorpay.key') ?: env('RAZORPAY_KEY');
        $this->secret = config('services.razorpay.secret') ?: env('RAZORPAY_SECRET');
        $this->webhookSecret = config('services.razorpay.webhook_secret') ?: env('RAZORPAY_WEBHOOK_SECRET');

        if ($this->key && $this->secret) {
            $this->razorpay = new Api($this->key, $this->secret);
        }
    }

    /**
     * Get underlying Razorpay API client
     */
    public function getApi(): ?Api
    {
        return $this->razorpay;
    }

    /**
     * Create Razorpay Order with notes & receipt
     */
    public function createOrder(Order $order, $receiptId)
    {
        if (!$this->razorpay) {
            throw new \Exception('Razorpay credentials not configured.');
        }

        $orderNumber = $order->order_number ?: (string)$receiptId;

        $params = [
            'receipt' => (string)$orderNumber,
            'amount' => (int)round($order->total_amount * 100),
            'currency' => 'INR',
            'payment_capture' => 1,
            'notes' => [
                'order_id' => (string)$order->id,
                'order_number' => (string)$orderNumber,
                'customer_phone' => (string)($order->customer_phone ?? ''),
                'customer_email' => (string)($order->email ?? ''),
            ],
        ];

        $razorpayOrder = $this->razorpay->order->create($params);

        $this->logEvent([
            'order_id' => $order->id,
            'order_number' => $orderNumber,
            'event_type' => 'order_created',
            'status' => 'initiated',
            'razorpay_order_id' => $razorpayOrder->id,
            'amount' => $order->total_amount,
            'message' => 'Checkout session initiated (Payment Pending). Razorpay Order ID: ' . $razorpayOrder->id,
            'payload' => [
                'receipt' => $params['receipt'],
                'amount' => $params['amount'],
                'notes' => $params['notes'],
            ],
        ]);

        return $razorpayOrder;
    }

    /**
     * Verify payment signature from checkout callback
     */
    public function verifySignature($orderId, $paymentId, $signature): bool
    {
        if (!$this->razorpay) {
            Log::error('Razorpay verifySignature failed: API not configured.');
            return false;
        }

        try {
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ];

            $this->razorpay->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (\Exception $e) {
            Log::error('Razorpay signature verification failed: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ]);

            $this->logEvent([
                'event_type' => 'callback',
                'status' => 'failed',
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'message' => 'Signature verification failed: ' . $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify Razorpay Webhook signature
     */
    public function validateWebhookSignature(string $requestBody, ?string $signature, ?string $secret = null): bool
    {
        if (empty($signature)) {
            Log::warning('Razorpay webhook verification failed: Missing X-Razorpay-Signature header.');
            return false;
        }

        $webhookSecret = $secret ?: $this->webhookSecret;

        if (empty($webhookSecret)) {
            Log::warning('Razorpay webhook secret not configured. Set RAZORPAY_WEBHOOK_SECRET in .env.');
            // If webhook secret is not configured in .env, log a critical warning
            return false;
        }

        try {
            if ($this->razorpay) {
                $this->razorpay->utility->verifyWebhookSignature($requestBody, $signature, $webhookSecret);
                return true;
            }

            // Fallback manual HMAC SHA256 verification
            $expectedSignature = hash_hmac('sha256', $requestBody, $webhookSecret);
            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('Razorpay webhook signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch order details from Razorpay API
     */
    public function fetchOrder(string $razorpayOrderId)
    {
        if (!$this->razorpay) {
            throw new \Exception('Razorpay credentials not configured.');
        }

        return $this->razorpay->order->fetch($razorpayOrderId);
    }

    /**
     * Fetch all payments for a given Razorpay Order ID
     */
    public function fetchOrderPayments(string $razorpayOrderId)
    {
        if (!$this->razorpay) {
            throw new \Exception('Razorpay credentials not configured.');
        }

        return $this->razorpay->order->fetch($razorpayOrderId)->payments();
    }

    /**
     * Fetch single payment details from Razorpay
     */
    public function fetchPayment(string $razorpayPaymentId)
    {
        if (!$this->razorpay) {
            throw new \Exception('Razorpay credentials not configured.');
        }

        return $this->razorpay->payment->fetch($razorpayPaymentId);
    }

    /**
     * Centralized, idempotent order recovery / mark as paid method.
     * Safe across: Callback, Webhook, Cron Sync, and Admin Action.
     */
    public function markOrderAsPaid(Order $order, string $paymentId, string $source, $rawPayload = null): array
    {
        if (is_object($rawPayload) && method_exists($rawPayload, 'toArray')) {
            $rawPayload = $rawPayload->toArray();
        } elseif (is_object($rawPayload)) {
            $rawPayload = (array)$rawPayload;
        }

        return DB::transaction(function () use ($order, $paymentId, $source, $rawPayload) {
            // Lock order record to prevent race conditions
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if (!$lockedOrder) {
                return [
                    'success' => false,
                    'already_paid' => false,
                    'message' => 'Order not found for locking',
                ];
            }

            // Check if order is already paid
            if ($lockedOrder->payment_status === 'paid') {
                $this->logEvent([
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'event_type' => $source,
                    'status' => 'skipped',
                    'razorpay_order_id' => $lockedOrder->razorpay_order_id,
                    'razorpay_payment_id' => $paymentId,
                    'amount' => $lockedOrder->total_amount,
                    'message' => "Order #{$lockedOrder->order_number} is already paid. Skipped duplicate processing.",
                    'payload' => $rawPayload,
                ]);

                return [
                    'success' => true,
                    'already_paid' => true,
                    'message' => 'Order already paid',
                    'order' => $lockedOrder,
                ];
            }

            // Update order status to paid and processing
            $lockedOrder->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'razorpay_payment_id' => $paymentId,
            ]);

            // Clear cart safely
            $this->clearCartSafely($lockedOrder);

            // Send notification emails safely (wrapped in try/catch to never fail transaction)
            $this->sendOrderEmailsSafely($lockedOrder);

            // Log recovery to DB & File
            $this->logEvent([
                'order_id' => $lockedOrder->id,
                'order_number' => $lockedOrder->order_number,
                'event_type' => $source,
                'status' => 'success',
                'razorpay_order_id' => $lockedOrder->razorpay_order_id,
                'razorpay_payment_id' => $paymentId,
                'amount' => $lockedOrder->total_amount,
                'message' => "Order #{$lockedOrder->order_number} successfully recovered/confirmed via {$source}. Payment ID: {$paymentId}",
                'payload' => $rawPayload,
            ]);

            Log::info("Razorpay Order Paid: #{$lockedOrder->order_number} via {$source}. Payment ID: {$paymentId}");

            return [
                'success' => true,
                'already_paid' => false,
                'message' => 'Order payment recovered successfully',
                'order' => $lockedOrder,
            ];
        });
    }

    /**
     * Clear customer cart safely without crashing
     */
    protected function clearCartSafely(Order $order): void
    {
        try {
            $cart = null;

            if ($order->session_id) {
                $cart = Cart::where('session_id', $order->session_id)->first();
            }

            if (!$cart && $order->user_id) {
                $cart = Cart::where('user_id', $order->user_id)->first();
            }

            if ($cart) {
                CartItem::where('cart_id', $cart->id)->delete();
                if ($cart->status === 'abandoned' || !empty($cart->recovery_token)) {
                    $cart->update([
                        'status' => 'recovered',
                        'recovered_at' => now(),
                        'recovered_order_id' => $order->id,
                    ]);
                } else {
                    $cart->delete();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error clearing cart for order #' . $order->order_number . ': ' . $e->getMessage());
        }
    }

    /**
     * Send order confirmation emails to customer, admin, and vendors
     */
    public function sendOrderEmailsSafely(Order $order): void
    {
        // 1. Customer Email
        try {
            if (!empty($order->email)) {
                Mail::to($order->email)->send(new OrderConfirmation($order));
            }
        } catch (\Throwable $e) {
            Log::warning('Customer order email sending failed for #' . $order->order_number . ': ' . $e->getMessage());
        }

        // 2. Admin Email
        try {
            $adminEmail = env('ADMIN_EMAIL');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new AdminOrderNotification($order));
            }
        } catch (\Throwable $e) {
            Log::warning('Admin order email sending failed for #' . $order->order_number . ': ' . $e->getMessage());
        }

        // 3. Vendor Emails
        try {
            $order->loadMissing(['items.product', 'items.vendor.user']);
            $vendorGroups = $order->items->whereNotNull('vendor_id')->groupBy('vendor_id');

            foreach ($vendorGroups as $vendorId => $vendorItems) {
                $vendor = $vendorItems->first()?->vendor ?? Vendor::with('user')->find($vendorId);
                $vendorEmail = $vendor?->user?->email;

                if ($vendor && $vendorEmail) {
                    Mail::to($vendorEmail)->send(new VendorOrderNotification($order, $vendor, $vendorItems));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Vendor order email sending failed for #' . $order->order_number . ': ' . $e->getMessage());
        }
    }

    /**
     * Structured event logging to DB (payment_logs) and file (Log::info/error)
     */
    public function logEvent(array $data): ?PaymentLog
    {
        try {
            $log = PaymentLog::create([
                'order_id' => $data['order_id'] ?? null,
                'order_number' => $data['order_number'] ?? null,
                'gateway' => $data['gateway'] ?? 'razorpay',
                'event_type' => $data['event_type'] ?? 'unknown',
                'status' => $data['status'] ?? 'pending',
                'razorpay_order_id' => $data['razorpay_order_id'] ?? null,
                'razorpay_payment_id' => $data['razorpay_payment_id'] ?? null,
                'amount' => $data['amount'] ?? null,
                'message' => $data['message'] ?? null,
                'payload' => $data['payload'] ?? null,
            ]);

            return $log;
        } catch (\Exception $e) {
            Log::error('Failed to write to payment_logs: ' . $e->getMessage(), $data);
            return null;
        }
    }
}