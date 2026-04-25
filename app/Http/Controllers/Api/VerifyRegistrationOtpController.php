<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VerifyRegistrationOtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:4',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->is_verified) {
            return $this->sendError('Email already verified', [], 400);
        }

        $isValid = $this->otpService->verifyRegistrationOtp(
            $request->email,
            $request->otp
        );

        if (! $isValid) {
            return $this->sendError('Invalid or expired OTP', [], 400);
        }

        $user->update(['is_verified' => true]);

        $token = auth('api')->login($user);
        $user->update(['jwt_token' => $token]);

        return $this->sendResponse([
            'user' => UserResource::make($user->loadMissing(['plan', 'subscriptions'])),
            'token' => $token,
        ], 'Email verified successfully', 200);
    }

    public function resend(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->is_verified) {
            return $this->sendError('Email already verified', [], 400);
        }

        $seconds = $this->otpService->getSecondsUntilNextAttempt($request->email);
        if ($seconds > 0) {
            return $this->sendError("Please wait {$seconds} seconds before requesting another OTP.", [], 429);
        }

        $sent = $this->otpService->sendRegistrationOtp($user);

        if (! $sent) {
            return $this->sendError('Failed to send OTP', [], 500);
        }

        return $this->sendResponse([
            'message' => 'OTP sent successfully',
        ], 'OTP sent to your email');
    }
}
