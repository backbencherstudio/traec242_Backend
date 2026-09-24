<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

#[Group('Authentication', weight: 1)]
class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('google_id', $googleUser->getId())->first();

            if (! $user) {
                $user = User::where('email', $googleUser->getEmail())->first();

                if (! $user) {
                    $fullName = (string) $googleUser->getName();
                    $parts = explode(' ', $fullName, 2);
                    $firstName = $parts[0] ?: $fullName;
                    $lastName = $parts[1] ?? null;

                    $user = User::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $googleUser->getEmail(),
                        'email_verified_at' => now(),
                        'google_id' => $googleUser->getId(),
                        'password' => bcrypt(Str::random(16)),
                    ]);
                } else {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                    ]);
                }
            }

            $jwtToken = auth('api')->login($user);

            return response()->json([
                'token' => $jwtToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
