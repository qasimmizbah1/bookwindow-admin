<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $lastName;
    public $email;
    public $subject;
    public $emailMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($firstName, $lastName, $email, $subject, $emailMessage)
    {
        $this->firstName = (string)$firstName;
        $this->lastName = (string)$lastName;
        $this->email = (string)$email;
        $this->subject = (string)$subject;
        $this->emailMessage = (string)$emailMessage;

        
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bookwindow Contact Form',
            to: 'qasimmizbah@gmail.com', // This sets the recipient
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}