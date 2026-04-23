<?php

namespace App\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;


class SendUserOtpMail extends Mailable implements ShouldQueue
{
    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Your OTP Code')
            ->view('emails.user_regi_otp');
    }
}
