<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Authentication', weight: 1)]
class VerifyRegistrationOtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();

        if ($user?->email_verified_at) {
            return $this->sendError('Email already verified', [], 400);
        }

        if (! $this->otpService->verifyRegistrationOtp($validated['email'], $validated['otp'])) {
            return $this->sendError('Invalid or expired OTP', [], 400);
        }

        $user->update(['email_verified_at' => now()]);

        $token = auth('api')->login($user);

        return $this->sendResponse([
            'user' => UserResource::make($user->loadMissing(['plan', 'subscriptions'])),
            'token' => $token,
        ], 'Email verified successfully', 200);
    }

    public function resend(ResendOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();

        if ($user?->email_verified_at) {
            return $this->sendError('Email already verified', [], 400);
        }

        $seconds = $this->otpService->getSecondsUntilNextAttempt($validated['email']);
        if ($seconds > 0) {
            return $this->sendError("Please wait {$seconds} seconds before requesting another OTP.", [], 429);
        }

        $sent = $this->otpService->sendRegistrationOtp($validated['email'], $user?->id);

        if (! $sent) {
            return $this->sendError('Failed to send OTP', [], 500);
        }

        return $this->sendResponse([
            'message' => 'OTP sent successfully',
        ], 'OTP sent to your email');
    }
}
