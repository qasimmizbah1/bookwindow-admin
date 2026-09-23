<?php 
namespace App\Observers;

use App\Models\Order;
use App\Mail\OrderStatusUpdatedMail;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    public function updated(Order $order)
    {
        // Check karein agar status field change hui hai
        if ($order->isDirty('status')) {
            $newStatus = $order->status;
            $customerEmail = $order->email ?? ($order->customer->email ?? null);

            if ($customerEmail) {
                try {
                    Mail::to($customerEmail)->send(new OrderStatusUpdatedMail($order, $newStatus));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("OrderObserver status email sending failed for Order #{$order->order_number}: " . $e->getMessage());
                }
            }
        }
    }
}
