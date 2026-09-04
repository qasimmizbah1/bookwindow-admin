<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Vendor $vendor;

    public function __construct(User $user, Vendor $vendor)
    {
        $this->user = $user;
        $this->vendor = $vendor;
    }

    public function build()
    {
        return $this->subject('Welcome to BookWindow - Vendor Application Received')
                    ->view('emails.vendor_welcome');
    }
}
