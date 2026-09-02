<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }

    public function build()
    {
        return $this->subject('Thank You for Registering')
                    ->view('emails.customer_thank_you');
    }
}