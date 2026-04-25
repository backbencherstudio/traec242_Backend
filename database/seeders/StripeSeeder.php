<?php

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
        $stripeSecretKey = (string) (config('services.stripe.secret') ?: 'sk_test_51SSTv4ALLuOtlOzLUirelW7TUbpKVijvozORdMAhCoDz8W5vJ493RQ3dZbppgBuQGQja2QcWxQdtRHjSRrtBhsnG009ykDA7g2');
        $stripePublicKey = (string) (config('services.stripe.key') ?: 'pk_test_51SSTv4ALLuOtlOzLGNLihqixur1Qum57SYcn2zxnoEjpkF4t5RVLg41SWkItnxu1nVfDX173IDTepQd7isJhEFXk00xDena5UA');

        return [
            'stripe_mode' => $this->resolveStripeMode($stripeSecretKey, $stripePublicKey),
            'stripe_secret_key' => $stripeSecretKey,
            'stripe_public_key' => $stripePublicKey,
            'stripe_webhook_secret' => (string) (config('cashier.webhook.secret') ?: 'whsec_C5fpm6YMGmsd9gCjcToS8nC0nJto9p6E'),
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
