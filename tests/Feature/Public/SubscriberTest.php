<?php

use App\Mail\SubscriberMail;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();
});

test('guest can subscribe to newsletter', function (): void {
    $response = $this->postJson('/api/subscriber', [
        'email' => 'newsletter@example.com',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.email', 'newsletter@example.com');

    $this->assertDatabaseHas('subscribers', [
        'email' => 'newsletter@example.com',
    ]);

    Mail::assertQueued(SubscriberMail::class, fn ($mail): bool => $mail->hasTo('newsletter@example.com'));
});

test('subscription requires a valid and unique email', function (): void {
    Subscriber::create(['email' => 'existing@example.com']);

    $responseDuplicate = $this->postJson('/api/subscriber', [
        'email' => 'existing@example.com',
    ]);

    $responseDuplicate->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $responseInvalid = $this->postJson('/api/subscriber', [
        'email' => 'not-an-email',
    ]);

    $responseInvalid->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('admin can list all subscribers', function (): void {
    $admin = User::factory()->create(['type' => 1]);
    Subscriber::create(['email' => 'sub1@example.com']);
    Subscriber::create(['email' => 'sub2@example.com']);

    $response = $this->actingAs($admin, 'api')
        ->getJson('/api/admin/subscriber/index');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});
