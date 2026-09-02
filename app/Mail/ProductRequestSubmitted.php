<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $formData;

    public function __construct($formData)
    {
        $this->formData = $formData;
    }

    public function build()
    {
        $email = $this->subject('New Product Request')
                    ->view('emails.product_request')
                    ->with(['data' => $this->formData]);

        if (!empty($this->formData['image_path'])) {
            $email->attach(storage_path('app/public/' . $this->formData['image_path']));
        }

        return $email;
    }
}