<?php

use App\Models\Service;
use App\Models\ServicePricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['jwt.secret' => 'testing-jwt-secret']);
});

test('create order requires a payment method id', function (): void {
    [$customer, $service, $pricing] = createOrderScenario();

    $response = $this->actingAs($customer, 'api')->postJson(
        '/api/admin/order/create-order',
        validOrderPayload($service, $pricing, withPaymentMethodId: false)
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_method_id']);
});

test('create order fails when provider has no stripe keys', function (): void {
    [$customer, $service, $pricing] = createOrderScenario();

    $response = $this->actingAs($customer, 'api')->postJson(
        '/api/admin/order/create-order',
        validOrderPayload($service, $pricing)
    );

    $response->assertStatus(404)
        ->assertJsonPath('status', false)
        ->assertJsonPath('error', 'Stripe key not found');

    $this->assertDatabaseMissing('orders', [
        'service_id' => $service->id,
        'user_id' => $customer->id,
    ]);
});

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

/**
 * @return array{0: User, 1: Service, 2: ServicePricing}
 */
function createOrderScenario(): array
{
    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Photography',
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $provider = createProviderUser([
        'category_id' => $categoryId,
    ]);

    $customer = createClientUser();

    $service = Service::create([
        'title' => 'Wedding Photography',
        'user_id' => $provider->id,
        'category_id' => $categoryId,
        'location' => 'Austin',
        'description' => 'Full day coverage',
        'image' => ['cover.jpg'],
        'feature_service' => false,
        'status' => true,
    ]);

    $pricing = ServicePricing::create([
        'service_id' => $service->id,
        'service_type' => 'basic',
        'duration' => '4 hours',
        'price' => 100,
        'description' => 'Basic package',
        'features' => ['Edited photos'],
    ]);

    return [$customer, $service, $pricing];
}

/**
 * @return array<string, mixed>
 */
function validOrderPayload(Service $service, ServicePricing $pricing, bool $withPaymentMethodId = true): array
{
    $payload = [
        'service_id' => $service->id,
        'service_pricing_id' => $pricing->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'phone' => '1234567890',
        'event_name' => 'Birthday Party',
        'event_start_date' => now()->addWeek()->toDateString(),
        'event_end_date' => now()->addWeek()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '14:00',
        'agree_terms' => true,
        'payment_method' => 'stripe',
    ];

    if ($withPaymentMethodId) {
        $payload['payment_method_id'] = 'pm_card_visa';
    }

    return $payload;
}
