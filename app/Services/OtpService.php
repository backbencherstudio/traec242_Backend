<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    public function sendRegistrationOtp(User $user): bool
    {
        $key = 'registration-otp-'.$user->email;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return false;
        }

        $otp = random_int(1000, 9999);

        DB::table('registration_otps')->updateOrInsert(
            ['email' => $user->email],
            [
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        try {
            Mail::raw(
                "Your verification OTP is: {$otp}. It will expire in 10 minutes.",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Email Verification OTP');
                }
            );
            RateLimiter::hit($key, 60);

            return true;
        } catch (\Exception $e) {
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
            DB::table('registration_otps')->where('email', $email)->delete();

            return false;
        }

        if (! Hash::check($otp, $record->otp)) {
            return false;
        }

        DB::table('registration_otps')->where('email', $email)->delete();

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
}
