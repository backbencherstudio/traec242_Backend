<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Stripe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StripeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Stripe::query()->updateOrCreate([], $this->stripeSettings());
    }

    /**
     * @return array{
     *     stripe_mode: string,
     *     stripe_secret_key: string,
     *     stripe_public_key: string,
     *     stripe_webhook_secret: string
     * }
     */
    protected function stripeSettings(): array
    {
        $stripeSecretKey = (string) (config('services.stripe.secret') ?: 'sk_test_placeholder');
        $stripePublicKey = (string) (config('services.stripe.key') ?: 'pk_test_placeholder');

        return [
            'stripe_mode' => $this->resolveStripeMode($stripeSecretKey, $stripePublicKey),
            'stripe_secret_key' => $stripeSecretKey,
            'stripe_public_key' => $stripePublicKey,
            'stripe_webhook_secret' => (string) (config('cashier.webhook.secret') ?: 'whsec_test_placeholder'),
        ];
    }

    protected function resolveStripeMode(string $stripeSecretKey, string $stripePublicKey): string
    {
        if (
            Str::startsWith($stripeSecretKey, 'sk_live_')
            || Str::startsWith($stripePublicKey, 'pk_live_')
        ) {
            return 'live';
        }

        return 'test';
    }
}
