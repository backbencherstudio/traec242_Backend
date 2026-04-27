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

    public function test_user_register_sends_otp_without_saving_the_user_when_otp_is_missing(): void
    {
        $response = $this->postJson('/api/user-register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonPath('data.requires_verification', true);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('registration_otps', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_registration_succeeds_when_valid_otp_is_submitted(): void
    {
        DB::table('registration_otps')->insert([
            'email' => 'test@example.com',
            'user_id' => null,
            'otp' => Hash::make('1234'),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/user-register', [
            'name' => 'Test User',
            'last_name' => 'Example',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'otp' => '1234',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'test@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'last_name' => 'Example',
            'is_verified' => true,
        ]);

        $this->assertDatabaseMissing('registration_otps', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_existing_unverified_user_can_still_verify_via_verify_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => false,
        ]);

        DB::table('registration_otps')->insert([
            'email' => $user->email,
            'user_id' => $user->id,
            'otp' => Hash::make('1234'),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/verify-email-otp', [
            'email' => 'test@example.com',
            'otp' => '1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'test@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_verified' => true,
        ]);
    }

    public function test_invalid_otp_prevents_user_registration(): void
    {
        DB::table('registration_otps')->insert([
            'email' => 'test@example.com',
            'user_id' => null,
            'otp' => Hash::make('9999'),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/user-register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'otp' => '1234',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid or expired OTP');

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_resend_otp_succeeds_for_unregistered_email(): void
    {
        $response = $this->postJson('/api/resend-email-otp', [
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
        User::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/resend-email-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Email already verified');
    }
}
