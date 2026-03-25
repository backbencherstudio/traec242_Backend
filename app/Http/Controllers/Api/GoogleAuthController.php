<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

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
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
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
            $user->update([
                'jwt_token' => $jwtToken,
            ]);

            return response()->json([
                'access_token' => $jwtToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 3600,
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
