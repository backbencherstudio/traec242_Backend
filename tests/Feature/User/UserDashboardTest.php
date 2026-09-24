<?php

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('user can fetch dashboard summary', function (): void {
    $user = createClientUser();

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/admin/user-dashboard/summary');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'upcoming_events',
                'past_orders',
                'unread_messages',
                'avg_rating_score',
            ],
        ]);
});

test('user can fetch recent orders using UserDashboardRecentOrderResource', function (): void {
    $user = createClientUser();
    $provider = createProviderUser();
    $category = Category::create(['name' => 'Photo', 'status' => 1]);
    $service = Service::create([
        'title' => 'Photography',
        'user_id' => $provider->id,
        'category_id' => $category->id,
        'description' => 'Great photos',
        'status' => 1,
    ]);

    $pricingId = DB::table('service_pricings')->insertGetId([
        'service_id' => $service->id,
        'service_type' => 'basic',
        'duration' => '4 hours',
        'price' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Order::create([
        'user_id' => $user->id,
        'service_id' => $service->id,
        'service_pricing_id' => $pricingId,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
        'event_name' => 'Annual Gala',
        'event_start_date' => now()->addDays(5)->toDateString(),
        'event_end_date' => now()->addDays(6)->toDateString(),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/admin/user-dashboard/recent-orders');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.event_name', 'Annual Gala')
        ->assertJsonPath('data.0.status', 'Pending');
});

test('user can fetch recent activity', function (): void {
    $user = createClientUser();

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/admin/user-dashboard/recent-activity');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

test('user can fetch recent messages using DashboardRecentMessageResource', function (): void {
    $user = createClientUser();
    $otherUser = User::factory()->create(['name' => 'Diana', 'last_name' => 'Prince']);
    $conversation = Conversation::create(['is_group' => false]);
    $conversation->users()->attach([$user->id, $otherUser->id]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $otherUser->id,
        'receiver_id' => $user->id,
        'message' => 'Hello there!',
        'type' => 'text',
    ]);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/admin/user-dashboard/recent-message');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Diana Prince')
        ->assertJsonPath('data.0.message', 'Hello there!');
});

test('user can fetch chat list using ChatConversationResource', function (): void {
    $user = createClientUser();
    $otherUser = User::factory()->create(['name' => 'Bruce', 'last_name' => 'Wayne']);
    $conversation = Conversation::create(['is_group' => false]);
    $conversation->users()->attach([$user->id, $otherUser->id]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $otherUser->id,
        'receiver_id' => $user->id,
        'message' => 'Let us schedule an event.',
        'type' => 'text',
    ]);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/admin/user-dashboard/chat-list');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Bruce Wayne')
        ->assertJsonPath('data.0.conversation_id', $conversation->id)
        ->assertJsonPath('data.0.unread_count', 1);
});
