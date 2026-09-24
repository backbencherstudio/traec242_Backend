<?php

use App\Models\Stripe;
use Database\Seeders\StripeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stripe seeder creates expected settings without duplicates', function (): void {
    config([
        'services.stripe.key' => 'pk_test_seeded_public_key',
        'services.stripe.secret' => 'sk_test_seeded_secret_key',
        'cashier.webhook.secret' => 'whsec_test_seeded_webhook_secret',
    ]);

    $this->seed(StripeSeeder::class);
    $this->seed(StripeSeeder::class);

    $this->assertDatabaseCount('stripes', 1);

    $this->assertDatabaseHas('stripes', [
        'stripe_mode' => 'test',
        'stripe_secret_key' => 'sk_test_seeded_secret_key',
        'stripe_public_key' => 'pk_test_seeded_public_key',
        'stripe_webhook_secret' => 'whsec_test_seeded_webhook_secret',
    ]);

    $stripeSettings = Stripe::query()->firstOrFail();

    expect($stripeSettings->stripe_mode)->toBe('test')
        ->and($stripeSettings->stripe_secret_key)->toBe('sk_test_seeded_secret_key')
        ->and($stripeSettings->stripe_public_key)->toBe('pk_test_seeded_public_key')
        ->and($stripeSettings->stripe_webhook_secret)->toBe('whsec_test_seeded_webhook_secret');
});

test('stripe seeder uses fallback values when config is missing', function (): void {
    config([
        'services.stripe.key' => null,
        'services.stripe.secret' => null,
        'cashier.webhook.secret' => null,
    ]);

    $this->seed(StripeSeeder::class);

    $this->assertDatabaseHas('stripes', [
        'stripe_mode' => 'test',
        'stripe_secret_key' => 'sk_test_placeholder',
        'stripe_public_key' => 'pk_test_placeholder',
        'stripe_webhook_secret' => 'whsec_test_placeholder',
    ]);
});
