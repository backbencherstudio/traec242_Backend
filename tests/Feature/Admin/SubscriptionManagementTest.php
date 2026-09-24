<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['type' => 1]);
});

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

function createSubscriptionProvider(array $overrides = []): User
{
    return User::factory()->create(array_merge(['type' => 2], $overrides));
}

function createSubscriptionRecord(User $provider, array $overrides = []): int
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

test('index returns all providers with subscription info', function () {
    $provider = createSubscriptionProvider();
    createSubscriptionRecord($provider);

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
});

test('index filters providers by name search', function () {
    createSubscriptionProvider(['name' => 'Alice', 'email' => 'alice@example.com']);
    createSubscriptionProvider(['name' => 'Bob', 'email' => 'bob@example.com']);

    $response = $this->actingAs($this->admin, 'api')
        ->getJson('/api/admin/subscriptions/providers?search=Alice');

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toContain('Alice');
});

test('index filters providers by subscription status', function () {
    $active = createSubscriptionProvider();
    createSubscriptionRecord($active, ['stripe_status' => 'active']);

    $canceled = createSubscriptionProvider();
    createSubscriptionRecord($canceled, [
        'stripe_status' => 'canceled',
        'ends_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->getJson('/api/admin/subscriptions/providers?status=active');

    $response->assertOk();
    $data = $response->json('data');

    $statuses = collect($data)->pluck('subscription_status')->unique()->values()->all();
    expect($statuses)->toBe(['active']);
});

test('index does not return non provider users', function () {
    User::factory()->create(['type' => 0]);
    User::factory()->create(['type' => 1]);
    $provider = createSubscriptionProvider();
    createSubscriptionRecord($provider);

    $response = $this->actingAs($this->admin, 'api')
        ->getJson('/api/admin/subscriptions/providers');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

// -------------------------------------------------------------------------
// GET /api/admin/subscriptions/providers/{id}
// -------------------------------------------------------------------------

test('show returns detailed subscription info', function () {
    $provider = createSubscriptionProvider();
    createSubscriptionRecord($provider);

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
});

test('show returns 404 for non existent provider', function () {
    $this->actingAs($this->admin, 'api')
        ->getJson('/api/admin/subscriptions/providers/9999')
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

test('show returns 404 for non provider user', function () {
    $client = User::factory()->create(['type' => 0]);

    $this->actingAs($this->admin, 'api')
        ->getJson("/api/admin/subscriptions/providers/{$client->id}")
        ->assertNotFound();
});

// -------------------------------------------------------------------------
// GET /api/admin/subscriptions/all
// -------------------------------------------------------------------------

test('all subscriptions returns subscriptions for providers only', function () {
    $provider = createSubscriptionProvider();
    createSubscriptionRecord($provider);

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
    expect($data)->toHaveCount(1)
        ->and($data[0]['provider_id'])->toBe($provider->id);
});

test('all subscriptions can filter by stripe status', function () {
    $p1 = createSubscriptionProvider();
    createSubscriptionRecord($p1, ['stripe_status' => 'active']);

    $p2 = createSubscriptionProvider();
    createSubscriptionRecord($p2, ['stripe_status' => 'paused']);

    $response = $this->actingAs($this->admin, 'api')
        ->getJson('/api/admin/subscriptions/all?status=active');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and($data[0]['stripe_status'])->toBe('active');
});

// -------------------------------------------------------------------------
// POST /api/admin/subscriptions/providers/{id}/cancel
// -------------------------------------------------------------------------

test('cancel returns 422 when provider has no subscription', function () {
    $provider = createSubscriptionProvider();

    $this->actingAs($this->admin, 'api')
        ->postJson("/api/admin/subscriptions/providers/{$provider->id}/cancel")
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

test('cancel returns 422 when subscription already canceled', function () {
    $provider = createSubscriptionProvider();
    createSubscriptionRecord($provider, [
        'stripe_status' => 'canceled',
        'ends_at' => now()->subDay(),
    ]);

    $this->actingAs($this->admin, 'api')
        ->postJson("/api/admin/subscriptions/providers/{$provider->id}/cancel")
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

test('cancel returns 404 for unknown provider', function () {
    $this->actingAs($this->admin, 'api')
        ->postJson('/api/admin/subscriptions/providers/9999/cancel')
        ->assertNotFound();
});

// -------------------------------------------------------------------------
// POST /api/admin/subscriptions/providers/{id}/pause
// -------------------------------------------------------------------------

test('pause returns 422 when provider has no active subscription', function () {
    $provider = createSubscriptionProvider();

    $this->actingAs($this->admin, 'api')
        ->postJson("/api/admin/subscriptions/providers/{$provider->id}/pause")
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

test('pause returns 422 when subscription is already paused', function () {
    $provider = createSubscriptionProvider();
    createSubscriptionRecord($provider, ['stripe_status' => 'paused']);

    $this->actingAs($this->admin, 'api')
        ->postJson("/api/admin/subscriptions/providers/{$provider->id}/pause")
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

// -------------------------------------------------------------------------
// POST /api/admin/subscriptions/providers/{id}/resume
// -------------------------------------------------------------------------

test('resume returns 422 when no subscription exists', function () {
    $provider = createSubscriptionProvider();

    $this->actingAs($this->admin, 'api')
        ->postJson("/api/admin/subscriptions/providers/{$provider->id}/resume")
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

test('resume returns 422 when subscription is active and not on grace period', function () {
    $provider = createSubscriptionProvider();
    createSubscriptionRecord($provider, ['stripe_status' => 'active']);

    $this->actingAs($this->admin, 'api')
        ->postJson("/api/admin/subscriptions/providers/{$provider->id}/resume")
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

// -------------------------------------------------------------------------
// POST /api/admin/subscriptions/providers/{id}/cancel-now
// -------------------------------------------------------------------------

test('cancel now returns 422 when no subscription exists', function () {
    $provider = createSubscriptionProvider();

    $this->actingAs($this->admin, 'api')
        ->postJson("/api/admin/subscriptions/providers/{$provider->id}/cancel-now")
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

test('cancel now returns 404 for unknown provider', function () {
    $this->actingAs($this->admin, 'api')
        ->postJson('/api/admin/subscriptions/providers/9999/cancel-now')
        ->assertNotFound();
});
