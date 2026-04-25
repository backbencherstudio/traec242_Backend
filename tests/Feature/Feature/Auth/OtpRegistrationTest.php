<?php

namespace Tests\Feature\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_sends_otp(): void
    {
        $response = $this->postJson('/user-register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'is_verified'],
                ],
            ])
            ->assertJsonPath('data.user.is_verified', false)
            ->assertJsonPath('data.user.email', 'test@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_verified' => false,
        ]);

        $this->assertDatabaseHas('registration_otps', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_otp_verification_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => false,
        ]);

        $otp = random_int(1000, 9999);

        DB::table('registration_otps')->insert([
            'email' => $user->email,
            'user_id' => $user->id,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/verify-email-otp', [
            'email' => 'test@example.com',
            'otp' => $otp,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_verified' => true,
        ]);
    }

    public function test_invalid_otp_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => false,
        ]);

        $otp = random_int(1000, 9999);

        DB::table('registration_otps')->insert([
            'email' => $user->email,
            'user_id' => $user->id,
            'otp' => Hash::make(9999),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/verify-email-otp', [
            'email' => 'test@example.com',
            'otp' => $otp,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid or expired OTP');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_verified' => false,
        ]);
    }

    public function test_expired_otp_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => false,
        ]);

        DB::table('registration_otps')->insert([
            'email' => $user->email,
            'user_id' => $user->id,
            'otp' => Hash::make(1234),
            'expires_at' => now()->subMinutes(1),
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        $response = $this->postJson('/verify-email-otp', [
            'email' => 'test@example.com',
            'otp' => '1234',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid or expired OTP');
    }

    public function test_already_verified_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => true,
        ]);

        $response = $this->postJson('/verify-email-otp', [
            'email' => 'test@example.com',
            'otp' => '1234',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Email already verified');
    }

    public function test_resend_otp_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => false,
        ]);

        $response = $this->postJson('/resend-email-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('registration_otps', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_resend_otp_fails_for_verified_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => true,
        ]);

        $response = $this->postJson('/resend-email-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Email already verified');
    }
}
