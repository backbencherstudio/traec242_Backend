<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminRegisterRequest;
use App\Http\Requests\Auth\AdminUpdateRequest;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\PasswordChangeRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OtpService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

#[Group('public-auth', weight: 1)]
class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected OtpService $otpService
    ) {}

    /**
     * List all administrator users.
     */
    #[Group('admin-auth', weight: 4)]
    public function index(): JsonResponse
    {
        $admins = User::role('admin')->get();

        return response()->json([
            'status' => 'success',
            'admin' => UserResource::collection($admins),
        ]);
    }

    /**
     * Authenticate a user and issue JWT.
     */
    public function login(ApiLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->email, $request->password);

            return response()->json([
                'user' => UserResource::make($result['user']->loadMissing(['plan', 'subscriptions'])),
                'message' => 'User login successfully',
                'token' => $result['token'],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'requires_verification' => true,
                'email' => $request->email,
            ], 403);
        } catch (\Throwable) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
    }

    /**
     * Register a regular user (with OTP verification).
     */
    public function register(UserRegisterRequest $request): JsonResponse
    {
        if (! $request->filled('otp')) {
            $secondsUntilNextAttempt = $this->otpService->getSecondsUntilNextAttempt($request->email);
            if ($secondsUntilNextAttempt > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$secondsUntilNextAttempt} seconds before requesting another OTP.",
                ], 429);
            }

            $otpSent = $this->otpService->sendRegistrationOtp($request->email);
            if (! $otpSent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email. Submit the registration again with the OTP to complete signup.',
                'data' => [
                    'email' => $request->email,
                    'requires_verification' => true,
                ],
            ], 200);
        }

        if (! $this->otpService->verifyRegistrationOtp($request->email, $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 400);
        }

        $result = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user' => UserResource::make($result['user']->loadMissing(['plan', 'subscriptions'])),
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Register a new admin user.
     */
    #[Group('admin-auth', weight: 4)]
    public function adminregister(AdminRegisterRequest $request): JsonResponse
    {
        $result = $this->authService->adminRegister($request->validated(), $request->file('image'));

        return response()->json([
            'success' => true,
            'message' => 'Admin registered successfully',
            'user' => UserResource::make($result['user']->loadMissing(['plan', 'subscriptions'])),
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Retrieve admin details for editing.
     */
    #[Group('admin-auth', weight: 4)]
    public function edit($id): JsonResponse
    {
        $user = User::role('admin')->find($id);
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'admin' => new UserResource($user),
        ], 200);
    }

    /**
     * Update an admin user.
     */
    #[Group('admin-auth', weight: 4)]
    public function adminUpdate(AdminUpdateRequest $request, $id): JsonResponse
    {
        $user = User::role('admin')->find($id);
        if (! $user) {
            return $this->sendError('Admin not found', [], 404);
        }

        $updated = $this->authService->adminUpdate($user, $request->validated(), $request->file('image'));

        return $this->sendResponse(new UserResource($updated), 'Admin updated successfully');
    }

    /**
     * Delete an admin user.
     */
    #[Group('admin-auth', weight: 4)]
    public function delete($id): JsonResponse
    {
        $user = User::role('admin')->find($id);
        if (! $user) {
            return $this->sendError('Admin not found', [], 404);
        }

        $user->syncRoles([]);
        $user->delete();

        return $this->sendResponse([], 'Admin deleted successfully');
    }

    /**
     * Get authenticated user profile.
     */
    #[Group('user-auth', weight: 2)]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        return response()->json([
            'success' => true,
            'user' => UserResource::make($user->loadMissing(['plan', 'subscriptions'])),
        ]);
    }

    /**
     * Log out current user and invalidate JWT token.
     */
    #[Group('user-auth', weight: 2)]
    public function logout(): JsonResponse
    {
        try {
            Auth::guard('api')->logout();
        } catch (\Throwable) {
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get admin password view or details.
     */
    #[Group('admin-auth', weight: 4)]
    public function password($id): JsonResponse
    {
        $admin = User::role('admin')->find($id);
        if (! $admin) {
            return $this->sendError('Admin not found', [], 404);
        }

        return $this->sendResponse(new UserResource($admin));
    }

    /**
     * Change authenticated user's password.
     */
    #[Group('user-auth', weight: 2)]
    public function passwordchange(PasswordChangeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $success = $this->authService->changePassword(
            $user,
            $request->current_password,
            $request->new_password
        );

        if (! $success) {
            return $this->sendError('Current password is incorrect', [], 400);
        }

        return $this->sendResponse([], 'Password changed successfully');
    }

    /**
     * Send OTP for forgot password.
     */
    public function sendOtp(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->sendForgotPasswordOtp($request->email);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email successfully',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 429);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP email',
            ], 500);
        }
    }

    /**
     * Verify OTP for forgot password.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $valid = $this->authService->verifyForgotPasswordOtp($request->email, $request->otp);

        if (! $valid) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
        ]);
    }

    /**
     * Reset password using OTP.
     */
    public function resetPasswordWithOtp(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPasswordWithOtp(
                $request->email,
                $request->otp,
                $request->password
            );

            return response()->json([
                'success' => true,
                'message' => 'Password set successfully!',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
