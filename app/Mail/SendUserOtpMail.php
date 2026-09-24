<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SendUserOtpMail extends Mailable implements ShouldQueue
{
    public function __construct(public int $otp) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Verification OTP Code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.user_regi_otp');
    }
}
