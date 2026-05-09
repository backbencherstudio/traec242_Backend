<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['type' => 1]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createProvider(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['type' => 2], $overrides));
    }

    private function createSubscription(User $provider, array $overrides = []): int
    {
        return DB::table('subscriptions')->insertGetId(array_merge([
            'user_id' => $provider->id,
            'type' => 'provider',
            'stripe_id' => 'sub_'.$provider->id.'_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test_monthly',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // GET /api/admin/subscriptions/providers
    // -------------------------------------------------------------------------

    public function test_index_returns_all_providers_with_subscription_info(): void
    {
        $provider = $this->createProvider();
        $this->createSubscription($provider);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/subscriptions/providers');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'email', 'plan', 'subscription_status',
                        'stripe_id', 'starts_at', 'ends_at',
                        'trial_ends_at', 'on_grace_period', 'is_paused', 'is_canceled',
                    ],
                ],
            ]);
    }

    public function test_index_filters_providers_by_name_search(): void
    {
        $this->createProvider(['name' => 'Alice', 'email' => 'alice@example.com']);
        $this->createProvider(['name' => 'Bob', 'email' => 'bob@example.com']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/subscriptions/providers?search=Alice');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Alice', $data[0]['name']);
    }

    public function test_index_filters_providers_by_subscription_status(): void
    {
        $active = $this->createProvider();
        $this->createSubscription($active, ['stripe_status' => 'active']);

        $canceled = $this->createProvider();
        $this->createSubscription($canceled, [
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/subscriptions/providers?status=active');

        $response->assertOk();
        $data = $response->json('data');

        $statuses = collect($data)->pluck('subscription_status')->unique()->values()->all();
        $this->assertEquals(['active'], $statuses);
    }

    public function test_index_does_not_return_non_provider_users(): void
    {
        User::factory()->create(['type' => 0]);
        User::factory()->create(['type' => 1]);
        $provider = $this->createProvider();
        $this->createSubscription($provider);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/subscriptions/providers');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // GET /api/admin/subscriptions/providers/{id}
    // -------------------------------------------------------------------------

    public function test_show_returns_detailed_subscription_info(): void
    {
        $provider = $this->createProvider();
        $this->createSubscription($provider);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/subscriptions/providers/{$provider->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $provider->id)
            ->assertJsonStructure([
                'data' => [
                    'stripe_subscription_id', 'stripe_status', 'all_subscriptions',
                ],
            ]);
    }

    public function test_show_returns_404_for_non_existent_provider(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/subscriptions/providers/9999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_show_returns_404_for_non_provider_user(): void
    {
        $client = User::factory()->create(['type' => 0]);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/subscriptions/providers/{$client->id}")
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // GET /api/admin/subscriptions/all
    // -------------------------------------------------------------------------

    public function test_all_subscriptions_returns_subscriptions_for_providers_only(): void
    {
        $provider = $this->createProvider();
        $this->createSubscription($provider);

        $clientUser = User::factory()->create(['type' => 0, 'stripe_id' => 'cus_client']);
        DB::table('subscriptions')->insert([
            'user_id' => $clientUser->id,
            'type' => 'provider',
            'stripe_id' => 'sub_client',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/subscriptions/all');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($provider->id, $data[0]['provider_id']);
    }

    public function test_all_subscriptions_can_filter_by_stripe_status(): void
    {
        $p1 = $this->createProvider();
        $this->createSubscription($p1, ['stripe_status' => 'active']);

        $p2 = $this->createProvider();
        $this->createSubscription($p2, ['stripe_status' => 'paused']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/subscriptions/all?status=active');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('active', $data[0]['stripe_status']);
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/subscriptions/providers/{id}/cancel
    // -------------------------------------------------------------------------

    public function test_cancel_returns_422_when_provider_has_no_subscription(): void
    {
        $provider = $this->createProvider();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/providers/{$provider->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_cancel_returns_422_when_subscription_already_canceled(): void
    {
        $provider = $this->createProvider();
        $this->createSubscription($provider, [
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/providers/{$provider->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_cancel_returns_404_for_unknown_provider(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/subscriptions/providers/9999/cancel')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/subscriptions/providers/{id}/pause
    // -------------------------------------------------------------------------

    public function test_pause_returns_422_when_provider_has_no_active_subscription(): void
    {
        $provider = $this->createProvider();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/providers/{$provider->id}/pause")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_pause_returns_422_when_subscription_is_already_paused(): void
    {
        $provider = $this->createProvider();
        $this->createSubscription($provider, ['stripe_status' => 'paused']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/providers/{$provider->id}/pause")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/subscriptions/providers/{id}/resume
    // -------------------------------------------------------------------------

    public function test_resume_returns_422_when_no_subscription_exists(): void
    {
        $provider = $this->createProvider();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/providers/{$provider->id}/resume")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_resume_returns_422_when_subscription_is_active_and_not_on_grace_period(): void
    {
        $provider = $this->createProvider();
        $this->createSubscription($provider, ['stripe_status' => 'active']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/providers/{$provider->id}/resume")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/subscriptions/providers/{id}/cancel-now
    // -------------------------------------------------------------------------

    public function test_cancel_now_returns_422_when_no_subscription_exists(): void
    {
        $provider = $this->createProvider();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/providers/{$provider->id}/cancel-now")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_cancel_now_returns_404_for_unknown_provider(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/subscriptions/providers/9999/cancel-now')
            ->assertNotFound();
    }
}
