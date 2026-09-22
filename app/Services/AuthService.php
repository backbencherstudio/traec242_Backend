<?php

namespace App\Services;

use App\Mail\ForgotPasswordOtpMail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        protected OtpService $otpService,
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Authenticate user with credentials.
     *
     * @return array{user: User, token: string}
     */
    public function login(string $email, string $password): array
    {
        if (! $token = Auth::guard('api')->attempt(['email' => $email, 'password' => $password])) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('api')->user();

        if (! $user->is_verified) {
            Auth::guard('api')->logout();

            $secondsUntilNextAttempt = $this->otpService->getSecondsUntilNextAttempt($user->email);
            if ($secondsUntilNextAttempt <= 0) {
                $this->otpService->sendRegistrationOtp($user->email, $user->id);
            }

            throw new \DomainException('Please verify your email before logging in.');
        }

        if ($user->jwt_token) {
            try {
                JWTAuth::setToken($user->jwt_token)->invalidate(true);
            } catch (\Throwable $e) {
                // Ignore invalidation failures for expired tokens
            }
        }

        $user->update(['jwt_token' => $token]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Register customer account.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'type' => 0,
            'password' => $data['password'],
            'is_verified' => true,
        ]);

        $token = Auth::guard('api')->login($user);
        $user->update(['jwt_token' => $token]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Register new admin user.
     *
     * @return array{user: User, token: string}
     */
    public function adminRegister(array $data, ?UploadedFile $image = null): array
    {
        $role = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'api',
        ]);

        $imagePath = null;
        if ($image) {
            $imagePath = $this->fileUploadService->upload($image, 'user');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'type' => 1,
            'status' => 1,
            'role' => $data['role'] ?? null,
            'image' => $imagePath,
            'password' => $data['password'],
            'is_verified' => true,
        ]);

        $user->assignRole($role->name);

        $token = Auth::guard('api')->login($user);
        $user->update(['jwt_token' => $token]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Update admin user profile.
     */
    public function adminUpdate(User $user, array $data, ?UploadedFile $image = null): User
    {
        if ($image) {
            $this->fileUploadService->delete($user->image);
            $data['image'] = $this->fileUploadService->upload($image, 'user');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if (! empty($data['role'])) {
            $role = Role::where('id', $data['role'])
                ->where('guard_name', 'api')
                ->first();

            if ($role) {
                $user->syncRoles([$role->name]);
            }
        }

        return $user;
    }

    /**
     * Send OTP for forgot password.
     */
    public function sendForgotPasswordOtp(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if ($user && (int) $user->type === 1) {
            throw new \DomainException('Admin cannot reset password via OTP. Please change password from dashboard.');
        }

        $key = 'otp-'.$email;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            throw new \DomainException("Please wait {$seconds} seconds before requesting another OTP.");
        }

        $otp = random_int(1000, 9999);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'otp' => Hash::make((string) $otp),
                'expires_at' => now()->addMinutes(5),
                'updated_at' => now(),
            ]
        );

        Mail::to($email)->send(new ForgotPasswordOtpMail($otp));
        RateLimiter::hit($key, 30);

        return true;
    }

    /**
     * Verify OTP for forgot password.
     */
    public function verifyForgotPasswordOtp(string $email, string $otp): bool
    {
        $record = DB::table('password_resets')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return false;
        }

        if (now()->gt($record->expires_at)) {
            return false;
        }

        return Hash::check($otp, $record->otp);
    }

    /**
     * Reset password using verified OTP.
     */
    public function resetPasswordWithOtp(string $email, string $otp, string $newPassword): bool
    {
        if (! $this->verifyForgotPasswordOtp($email, $otp)) {
            throw new \DomainException('Invalid or expired OTP.');
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->password = $newPassword;
        $user->save();

        // Invalidate OTP after successful reset
        DB::table('password_resets')->where('email', $email)->delete();

        return true;
    }

    /**
     * Change authenticated user password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->password = $newPassword;
        $user->save();

        return true;
    }
}
