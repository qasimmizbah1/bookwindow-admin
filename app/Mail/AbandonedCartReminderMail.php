<?php

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cart $cart;
    public string $recoveryUrl;
    public string $mailSubject;
    public string $messageBody;

    public function __construct(Cart $cart, ?string $mailSubject = null, ?string $messageBody = null)
    {
        $this->cart = $cart->loadMissing(['items.product', 'customer']);
        $this->recoveryUrl = $cart->getRecoveryUrl();
        $this->mailSubject = $mailSubject ?: 'Items waiting in your shopping bag - Bookwindow';
        $this->messageBody = $messageBody ?: $cart->getReminderMessageText();
    }

    public function build()
    {
        return $this->subject($this->mailSubject)
            ->view('emails.abandoned-cart-reminder');
    }
}
