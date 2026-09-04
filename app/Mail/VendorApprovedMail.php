<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorApprovedMail extends Mailable
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
        $storeName = $this->vendor->vendor_name ?: $this->user->name;

        return $this->subject("Congratulations! Your BookWindow Vendor Account is Approved - {$storeName}")
                    ->view('emails.vendor_approved');
    }
}
