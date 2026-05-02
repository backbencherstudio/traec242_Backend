<?php

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ForgotPasswordOtpMail extends Mailable implements ShouldQueue
{
    public function __construct(public int $otp) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Password Reset OTP – '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.forgot_password_otp');
    }
}
