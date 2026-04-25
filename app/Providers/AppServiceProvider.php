<?php

namespace App\Providers;

use App\Models\Stripe as StripeSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. Super Admin Gate Bypass
        // This allows Super Admins to bypass all permission checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        try {
            if (Schema::hasTable('settings')) {
                $settings = DB::table('settings')->first();

                if ($settings) {
                    config([
                        'broadcasting.connections.pusher.id' => $settings->pusher_app_id,
                        'broadcasting.connections.pusher.key' => $settings->pusher_app_key,
                        'broadcasting.connections.pusher.secret' => $settings->pusher_app_secret,
                        'broadcasting.connections.pusher.options.cluster' => $settings->pusher_app_cluster,

                        'services.google.client_id' => $settings->google_client_id,
                        'services.google.client_secret' => $settings->google_client_secret,
                        'services.google.redirect' => $settings->google_redirect_url,
                        'services.google.scopes' => ['openid', 'profile', 'email'],
                    ]);
                }
            }

            if (Schema::hasTable('stripes')) {
                $stripeSettings = StripeSettings::query()->first();

                if ($stripeSettings) {
                    config([
                        'services.stripe.key' => $stripeSettings->stripe_public_key,
                        'services.stripe.secret' => $stripeSettings->stripe_secret_key,
                        'cashier.key' => $stripeSettings->stripe_public_key,
                        'cashier.secret' => $stripeSettings->stripe_secret_key,
                        'cashier.webhook.secret' => $stripeSettings->stripe_webhook_secret,
                    ]);
                }
            }
        } catch (\Exception $e) {
            //
        }

        Event::listen(WebhookReceived::class, function (WebhookReceived $event): void {
            $payload = $event->payload;

            if (($payload['type'] ?? null) !== 'invoice.payment_failed') {
                return;
            }

            $customerId = $payload['data']['object']['customer'] ?? null;
            $subscriptionId = $payload['data']['object']['subscription'] ?? null;

            if (! $customerId || ! $subscriptionId) {
                return;
            }

            $user = Cashier::findBillable($customerId);

            if (! $user) {
                return;
            }

            $subscription = $user->subscriptions()->where('stripe_id', $subscriptionId)->first();

            if (! $subscription || $subscription->canceled()) {
                return;
            }

            $subscription->cancelNow();
        });
    }
}
