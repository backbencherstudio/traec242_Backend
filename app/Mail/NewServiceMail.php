<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewServiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $title;

    public string $description;

    public function __construct($service)
    {
        $this->title = $service->title;
        $this->description = $service->description;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Service Available on Evoke!');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new_service');
    }
}
