<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminOrderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'New Order Received - #' . $this->order->order_number,
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'emails.admin-order-notification',
        );
    }
}