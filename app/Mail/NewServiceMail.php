<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class NewServiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function build()
    {
        return $this->subject('New Service Added!')
                    ->view('emails.new_service');
    }
}
