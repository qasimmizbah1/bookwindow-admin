<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminNewCustomerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }

    public function build()
    {
        return $this->subject('New Customer Registration')
                    ->view('emails.admin_new_customer');
    }
}