<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $orderItems;
    public $status;

    public function __construct(Order $order, string $status)
    {
        $this->order = $order;
        // Products ke sath order items load karein taaki image aur name mil sake
        $this->orderItems = $order->items()->with('product')->get();
        $this->status = $status;
    }

    public function build()
    {
        return $this->subject("Update on your Order #{$this->order->order_number}: " . ucfirst($this->status))
                    ->view('emails.order-status-updated');
    }
}
