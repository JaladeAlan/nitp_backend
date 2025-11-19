<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function build()
    {
        return $this->subject('Thank You for Contacting NITP Oyo')
                    ->view('emails.contact-autoreply')
                    ->with([
                        'name' => $this->name,
                    ]);
    }
}
