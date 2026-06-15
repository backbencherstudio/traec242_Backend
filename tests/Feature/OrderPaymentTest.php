<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServicePricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => 'testing-jwt-secret']);
    }

    public function test_create_order_requires_a_payment_method_id(): void
    {
        [$customer, $service, $pricing] = $this->createOrderScenario();

        $response = $this->actingAs($customer, 'api')->postJson(
            '/api/admin/order/create-order',
            $this->validOrderPayload($service, $pricing, withPaymentMethodId: false)
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method_id']);
    }

    public function test_create_order_fails_when_provider_has_no_stripe_keys(): void
    {
        [$customer, $service, $pricing] = $this->createOrderScenario();

        $response = $this->actingAs($customer, 'api')->postJson(
            '/api/admin/order/create-order',
            $this->validOrderPayload($service, $pricing)
        );

        $response->assertStatus(404)
            ->assertJsonPath('status', false)
            ->assertJsonPath('error', 'Stripe key not found');

        $this->assertDatabaseMissing('orders', [
            'service_id' => $service->id,
            'user_id' => $customer->id,
        ]);
    }

    /**
     * @return array{0: User, 1: Service, 2: ServicePricing}
     */
    private function createOrderScenario(): array
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Photography',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $provider = User::factory()->create([
            'type' => 2,
            'category_id' => $categoryId,
        ]);

        $customer = User::factory()->create([
            'type' => 0,
        ]);

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
    private function validOrderPayload(Service $service, ServicePricing $pricing, bool $withPaymentMethodId = true): array
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
}
