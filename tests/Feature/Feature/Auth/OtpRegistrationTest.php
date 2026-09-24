<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user register sends otp without saving the user when otp is missing', function (): void {
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
});

test('user registration succeeds when valid otp is submitted', function (): void {
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
});

test('existing unverified user can still verify via verify endpoint', function (): void {
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
});

test('invalid otp prevents user registration', function (): void {
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
});

test('resend otp succeeds for unregistered email', function (): void {
    $response = $this->postJson('/api/resend-email-otp', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('registration_otps', [
        'email' => 'test@example.com',
    ]);
});

test('resend otp fails for verified email', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'is_verified' => true,
    ]);

    $response = $this->postJson('/api/resend-email-otp', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('message', 'Email already verified');
});
