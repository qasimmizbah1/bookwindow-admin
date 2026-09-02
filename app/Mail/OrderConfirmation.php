<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $orderItems;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->orderItems = $order->items; 
    }

    public function build()
    {
        return $this->subject('Order Confirmation')
                    ->view('emails.order-confirmation');
    }

    
}