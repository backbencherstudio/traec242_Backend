<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    public function sendRegistrationOtp(string $email, ?int $userId = null): bool
    {
        $key = $this->getRateLimitKey($email);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return false;
        }

        $otp = random_int(1000, 9999);

        DB::table('registration_otps')->updateOrInsert(
            ['email' => $email],
            [
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        try {
            Mail::raw(
                "Your verification OTP is: {$otp}. It will expire in 10 minutes.",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Email Verification OTP');
                }
            );
            RateLimiter::hit($key, 60);

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public function verifyRegistrationOtp(string $email, string $otp): bool
    {
        $record = DB::table('registration_otps')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return false;
        }

        if (now()->gt($record->expires_at)) {
            $this->deleteRegistrationAttempt($email);

            return false;
        }

        if (! Hash::check($otp, $record->otp)) {
            return false;
        }

        $this->deleteRegistrationAttempt($email);

        return true;
    }

    public function getRateLimitKey(string $email): string
    {
        return 'registration-otp-'.$email;
    }

    public function getSecondsUntilNextAttempt(string $email): int
    {
        return RateLimiter::availableIn($this->getRateLimitKey($email));
    }

    public function deleteRegistrationAttempt(string $email): void
    {
        DB::table('registration_otps')
            ->where('email', $email)
            ->delete();
    }
}
