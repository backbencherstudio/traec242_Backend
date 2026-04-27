<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\Subscription;
use Tests\TestCase;

class ProviderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_registration_sends_otp_first_and_only_creates_the_account_after_otp_confirmation(): void
    {
        $this->fakeStripeClient();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Photography',
            'slug' => 'photography',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'premium',
            'title' => 'Provider Monthly Plan',
            'price' => 100,
            'currency' => 'USD',
            'package' => 'monthly',
            'day' => 30,
            'features' => json_encode(['Featured provider listing']),
            'stripe_product_id' => 'prod_provider',
            'stripe_price_id' => 'price_provider_monthly',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $initialResponse = $this->postJson('/api/register_provider', [
            'name' => 'John',
            'last_name' => 'Provider',
            'email' => 'provider@example.com',
            'phone' => '123456789',
            'address' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'Texas',
            'zip_code' => '78701',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'category_id' => [$categoryId],
            'plan_id' => $planId,
            'payment_method' => 'pm_card_visa',
        ]);

        $initialResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'provider@example.com')
            ->assertJsonPath('data.requires_verification', true);

        $this->assertDatabaseMissing('users', [
            'email' => 'provider@example.com',
        ]);

        DB::table('registration_otps')
            ->where('email', 'provider@example.com')
            ->update([
                'otp' => Hash::make('1234'),
                'updated_at' => now(),
            ]);

        $finalResponse = $this->postJson('/api/register_provider', [
            'name' => 'John',
            'last_name' => 'Provider',
            'email' => 'provider@example.com',
            'phone' => '123456789',
            'address' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'Texas',
            'zip_code' => '78701',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'category_id' => [$categoryId],
            'plan_id' => $planId,
            'payment_method' => 'pm_card_visa',
            'otp' => '1234',
        ]);

        $finalResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.type', 'provider')
            ->assertJsonPath('data.user.plan_id', $planId)
            ->assertJsonPath('data.user.provider_subscription.status', 'subscribed')
            ->assertJsonPath('data.user.provider_subscription.stripe_status', 'active');

        $this->assertDatabaseHas('users', [
            'email' => 'provider@example.com',
            'type' => 2,
            'plan_id' => $planId,
            'stripe_id' => 'cus_provider_test',
            'pm_last_four' => '4242',
            'is_verified' => true,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'type' => 'provider',
            'stripe_id' => 'sub_provider_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_provider_monthly',
        ]);
    }

    public function test_provider_registration_requires_a_valid_otp(): void
    {
        $this->fakeStripeClient();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Photography',
            'slug' => 'photography',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'premium',
            'title' => 'Provider Monthly Plan',
            'price' => 100,
            'currency' => 'USD',
            'package' => 'monthly',
            'day' => 30,
            'features' => json_encode(['Featured provider listing']),
            'stripe_product_id' => 'prod_provider',
            'stripe_price_id' => 'price_provider_monthly',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('registration_otps')->insert([
            'email' => 'provider@example.com',
            'user_id' => null,
            'otp' => Hash::make('9999'),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/register_provider', [
            'name' => 'John',
            'last_name' => 'Provider',
            'email' => 'provider@example.com',
            'phone' => '123456789',
            'address' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'Texas',
            'zip_code' => '78701',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'category_id' => [$categoryId],
            'plan_id' => $planId,
            'payment_method' => 'pm_card_visa',
            'otp' => '1234',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid or expired OTP');

        $this->assertDatabaseMissing('users', [
            'email' => 'provider@example.com',
        ]);
    }

    public function test_provider_registration_requires_a_monthly_billable_plan(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Photography',
            'slug' => 'photography',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'free',
            'title' => 'Free Plan',
            'price' => 0,
            'currency' => 'USD',
            'package' => 'free',
            'day' => 7,
            'features' => json_encode([]),
            'stripe_product_id' => null,
            'stripe_price_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/register_provider', [
            'name' => 'John',
            'last_name' => 'Provider',
            'email' => 'provider@example.com',
            'phone' => '123456789',
            'address' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'Texas',
            'zip_code' => '78701',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'category_id' => [$categoryId],
            'plan_id' => $planId,
            'payment_method' => 'pm_card_visa',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }

    protected function fakeStripeClient(): void
    {
        $customers = new class
        {
            public ?string $defaultPaymentMethod = null;

            public function create(array $options, array $requestOptions = []): object
            {
                return Customer::constructFrom([
                    'id' => 'cus_provider_test',
                ]);
            }

            public function retrieve(string $customerId, array $params = []): object
            {
                return Customer::constructFrom([
                    'id' => $customerId,
                    'invoice_settings' => [
                        'default_payment_method' => $this->defaultPaymentMethod,
                    ],
                ]);
            }

            public function update(string $customerId, array $options): object
            {
                $this->defaultPaymentMethod = $options['invoice_settings']['default_payment_method'] ?? null;

                return Customer::constructFrom([
                    'id' => $customerId,
                    'invoice_settings' => [
                        'default_payment_method' => $this->defaultPaymentMethod,
                    ],
                ]);
            }
        };

        $paymentMethods = new class
        {
            public function retrieve(string $paymentMethodId): object
            {
                return PaymentMethod::constructFrom([
                    'id' => $paymentMethodId,
                    'customer' => 'cus_provider_test',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                    ],
                ]);
            }
        };

        $subscriptions = new class
        {
            public function create(array $payload): object
            {
                return new class extends Subscription
                {
                    public string $id = 'sub_provider_test';

                    public string $status = 'active';

                    public Collection $items;

                    public function __construct()
                    {
                        $this->items = collect([
                            (object) [
                                'id' => 'si_provider_test',
                                'price' => (object) [
                                    'id' => 'price_provider_monthly',
                                    'product' => 'prod_provider',
                                ],
                                'quantity' => 1,
                            ],
                        ]);
                    }
                };
            }
        };

        $stripeClient = new class($customers, $paymentMethods, $subscriptions) extends StripeClient
        {
            public function __construct(
                public object $customers,
                public object $paymentMethods,
                public object $subscriptions,
            ) {}
        };

        $this->app->bind(StripeClient::class, fn () => $stripeClient);
    }
}
