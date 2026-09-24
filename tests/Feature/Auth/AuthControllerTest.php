<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['jwt.secret' => 'a9f7e8c3b2d1e0f4a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b9c8d7e6f5']);
    Mail::fake();
});

test('user can login with valid credentials', function (): void {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'secret123',
        'is_verified' => true,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'user' => ['id', 'email', 'name'],
            'token',
            'message',
        ]);
});

test('login fails with invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'secret123',
        'is_verified' => true,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Invalid credentials']);
});

test('unverified user cannot login', function (): void {
    User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => 'secret123',
        'is_verified' => false,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'unverified@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('requires_verification', true)
        ->assertJsonPath('email', 'unverified@example.com');
});

test('authenticated user can fetch own profile via me endpoint', function (): void {
    $user = User::factory()->create([
        'email' => 'profile@example.com',
        'is_verified' => true,
    ]);

    $response = $this->actingAs($user, 'api')->getJson('/api/me');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.email', 'profile@example.com');
});

test('authenticated user can change password', function (): void {
    $user = User::factory()->create([
        'password' => 'oldpassword123',
        'is_verified' => true,
    ]);

    $response = $this->actingAs($user, 'api')->postJson('/api/profile/passwordchange', [
        'current_password' => 'oldpassword123',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Password changed successfully');

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});

test('user cannot change password with incorrect current password', function (): void {
    $user = User::factory()->create([
        'password' => 'oldpassword123',
        'is_verified' => true,
    ]);

    $response = $this->actingAs($user, 'api')->postJson('/api/profile/passwordchange', [
        'current_password' => 'wrongpassword',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Current password is incorrect');
});

test('user can send forgot password otp', function (): void {
    $user = User::factory()->create(['email' => 'forgot@example.com']);

    $response = $this->postJson('/api/forgot-password', [
        'email' => 'forgot@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'OTP sent to your email successfully');

    $this->assertDatabaseHas('password_resets', [
        'email' => 'forgot@example.com',
    ]);
});

test('user can verify forgot password otp', function (): void {
    User::factory()->create(['email' => 'reset@example.com']);

    DB::table('password_resets')->insert([
        'email' => 'reset@example.com',
        'otp' => Hash::make('1234'),
        'expires_at' => now()->addMinutes(10),
        'updated_at' => now(),
    ]);

    $response = $this->postJson('/api/verify-otp', [
        'email' => 'reset@example.com',
        'otp' => '1234',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'OTP verified successfully');
});

test('user can reset password using valid otp', function (): void {
    $user = User::factory()->create(['email' => 'resetme@example.com']);

    DB::table('password_resets')->insert([
        'email' => 'resetme@example.com',
        'otp' => Hash::make('4321'),
        'expires_at' => now()->addMinutes(10),
        'updated_at' => now(),
    ]);

    $response = $this->postJson('/api/reset-password', [
        'email' => 'resetme@example.com',
        'otp' => '4321',
        'password' => 'brandnewpassword123',
        'password_confirmation' => 'brandnewpassword123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Password set successfully!');

    expect(Hash::check('brandnewpassword123', $user->fresh()->password))->toBeTrue();
});

test('authenticated user can logout', function (): void {
    $user = User::factory()->create(['jwt_token' => 'dummy-token']);

    $response = $this->actingAs($user, 'api')->postJson('/api/admin/logout');

    $response->assertOk()
        ->assertJsonPath('message', 'Logged out successfully');
});
