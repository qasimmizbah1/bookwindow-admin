<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:check-abandoned {--minutes= : Override wait time minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identify and mark inactive shopping carts as abandoned based on admin configured wait time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Get wait time from option, settings table, or default to 60 mins
        $waitTimeMinutes = (int) ($this->option('minutes') ?: Setting::get('abandoned_cart_wait_time_minutes', 60));
        if ($waitTimeMinutes < 1) {
            $waitTimeMinutes = 60;
        }

        $cutoffTime = now()->subMinutes($waitTimeMinutes);
        $this->info("Checking carts inactive since {$cutoffTime->toDateTimeString()} ({$waitTimeMinutes} min threshold)...");

        // 2. Find active carts that have items and haven't been updated since cutoff time
        $activeCarts = Cart::with(['items', 'customer'])
            ->where('status', 'active')
            ->has('items')
            ->where('updated_at', '<=', $cutoffTime)
            ->get();

        $abandonedCount = 0;
        $recoveredCount = 0;

        foreach ($activeCarts as $cart) {
            // Check if customer already placed an order for this session or user after cart was created/updated
            $hasPaidOrder = Order::where(function ($query) use ($cart) {
                if ($cart->user_id) {
                    $query->where('user_id', $cart->user_id);
                }
                if ($cart->session_id) {
                    $query->orWhere('session_id', $cart->session_id);
                }
            })
            ->whereIn('payment_status', ['paid', 'completed'])
            ->where('created_at', '>=', $cart->created_at)
            ->exists();

            if ($hasPaidOrder) {
                // If paid order already exists, this cart was already converted
                $cart->update([
                    'status' => 'recovered',
                    'recovered_at' => now(),
                ]);
                $recoveredCount++;
                continue;
            }

            // Sync customer details from Customer relation if available
            $name = $cart->customer_name;
            $email = $cart->customer_email;
            $phone = $cart->customer_phone;

            if ($cart->customer) {
                if (empty($name)) {
                    $name = trim(($cart->customer->first_name ?? '') . ' ' . ($cart->customer->last_name ?? ''));
                }
                if (empty($email)) {
                    $email = $cart->customer->email;
                }
                if (empty($phone)) {
                    $phone = $cart->customer->phone;
                }
            }

            $cart->ensureRecoveryToken();

            $cart->update([
                'status' => 'abandoned',
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'abandoned_at' => $cart->updated_at ?? now(),
            ]);

            $abandonedCount++;
        }

        // 3. Mark carts older than 30 days as expired
        $expiredCutoff = now()->subDays(30);
        $expiredCount = Cart::where('status', 'abandoned')
            ->where('abandoned_at', '<=', $expiredCutoff)
            ->update([
                'status' => 'expired',
            ]);

        $this->info("Completed: {$abandonedCount} carts marked as Abandoned, {$recoveredCount} resolved as Recovered, {$expiredCount} marked as Expired.");

        return Command::SUCCESS;
    }
}
