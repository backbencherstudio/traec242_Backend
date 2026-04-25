<?php

namespace Tests\Feature;

use App\Models\Stripe;
use Database\Seeders\StripeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the seeded Stripe settings are created only once.
     */
    public function test_stripe_seeder_creates_expected_settings_without_duplicates(): void
    {
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

        $this->assertSame('test', $stripeSettings->stripe_mode);
        $this->assertSame('sk_test_seeded_secret_key', $stripeSettings->stripe_secret_key);
        $this->assertSame('pk_test_seeded_public_key', $stripeSettings->stripe_public_key);
        $this->assertSame('whsec_test_seeded_webhook_secret', $stripeSettings->stripe_webhook_secret);
    }

    public function test_stripe_seeder_uses_fallback_values_when_config_is_missing(): void
    {
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
    }
}
