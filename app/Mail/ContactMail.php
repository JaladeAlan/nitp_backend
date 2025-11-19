<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $messageBody;

    public function __construct($name, $email, $messageBody)
    {
        $this->name = $name;
        $this->email = $email;
        $this->messageBody = $messageBody;
    }

    public function build()
    {
        return $this->subject('New Contact Form Message')
            ->view('emails.contact')
            ->with([
                'name' => $this->name,
                'email' => $this->email,
                'messageBody' => $this->messageBody,
            ]);
    }
}
