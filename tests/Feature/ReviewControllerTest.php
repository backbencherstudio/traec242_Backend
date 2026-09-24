<?php

use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['jwt.secret' => 'testing-jwt-secret']);
    Mail::fake();
});

// -------------------------------------------------------------------------
// Store
// -------------------------------------------------------------------------

test('customer can store a review for a completed order', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create();
    $order = createCompletedOrderForReview($customer, $service);

    $response = $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
        'order_id' => $order->id,
        'review' => 'Great work!',
        'rating' => 5,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.order_id', $order->id);

    $this->assertDatabaseHas('reviews', [
        'user_id' => $customer->id,
        'order_id' => $order->id,
        'service_id' => $service->id,
        'rating' => 5,
        'review' => 'Great work!',
    ]);
});

test('customer cannot review another users order', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create();
    $stranger = User::factory()->create();
    $order = createCompletedOrderForReview($customer, $service);

    $this->actingAs($stranger, 'api')->postJson('/api/admin/review/store', [
        'order_id' => $order->id,
        'rating' => 4,
    ])->assertForbidden();

    $this->assertDatabaseCount('reviews', 0);
});

test('customer cannot review an order that is not completed', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create();
    $order = createCompletedOrderForReview($customer, $service, 'pending');

    $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
        'order_id' => $order->id,
        'rating' => 5,
    ])->assertForbidden();

    $this->assertDatabaseCount('reviews', 0);
});

test('customer cannot review the same order twice', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create();
    $order = createCompletedOrderForReview($customer, $service);

    Review::create([
        'user_id' => $customer->id,
        'order_id' => $order->id,
        'service_id' => $service->id,
        'rating' => 5,
    ]);

    $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
        'order_id' => $order->id,
        'rating' => 3,
    ])->assertStatus(409);

    $this->assertDatabaseCount('reviews', 1);
});

test('store requires an order id and valid rating', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
        'rating' => 6,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['order_id', 'rating']);
});

// -------------------------------------------------------------------------
// Reply
// -------------------------------------------------------------------------

test('service owner can reply to a review and it persists', function () {
    [$service, $owner] = createReviewService();
    $customer = User::factory()->create();

    $review = Review::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'rating' => 5,
        'review' => 'Loved it',
    ]);

    $response = $this->actingAs($owner, 'api')->patchJson('/api/admin/review/reply/'.$review->id, [
        'reply' => 'Thank you!',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.reply', 'Thank you!');

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'reply' => 'Thank you!',
    ]);
});

test('non owner cannot reply to a review', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create();
    $stranger = User::factory()->create();

    $review = Review::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'rating' => 5,
    ]);

    $this->actingAs($stranger, 'api')->patchJson('/api/admin/review/reply/'.$review->id, [
        'reply' => 'Sneaky reply',
    ])->assertForbidden();

    $this->assertDatabaseMissing('reviews', [
        'id' => $review->id,
        'reply' => 'Sneaky reply',
    ]);
});

// -------------------------------------------------------------------------
// Received
// -------------------------------------------------------------------------

test('provider sees only reviews on their own services with reply status', function () {
    [$service, $owner] = createReviewService();
    [$otherService] = createReviewService();
    $customer = User::factory()->create();

    $mine = Review::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'rating' => 5,
        'review' => 'Loved it',
    ]);

    Review::create([
        'user_id' => $customer->id,
        'service_id' => $otherService->id,
        'rating' => 4,
    ]);

    $this->actingAs($owner, 'api')->getJson('/api/admin/review/received')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id)
        ->assertJsonPath('data.0.has_replied', false);
});

test('provider received review shows replied after responding', function () {
    [$service, $owner] = createReviewService();
    $customer = User::factory()->create();

    $review = Review::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'rating' => 5,
    ]);

    $this->actingAs($owner, 'api')->patchJson('/api/admin/review/reply/'.$review->id, [
        'reply' => 'Thank you!',
    ])->assertOk();

    $this->actingAs($owner, 'api')->getJson('/api/admin/review/received')
        ->assertOk()
        ->assertJsonPath('data.0.has_replied', true)
        ->assertJsonPath('data.0.reply', 'Thank you!');
});

// -------------------------------------------------------------------------
// Show
// -------------------------------------------------------------------------

test('review author can show their own review', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create();

    $review = Review::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'rating' => 5,
        'review' => 'Loved it',
    ]);

    $this->actingAs($customer, 'api')->getJson('/api/admin/review/show/'.$review->id)
        ->assertOk()
        ->assertJsonPath('data.id', $review->id)
        ->assertJsonPath('data.rating', 5);
});

test('service owner can show a review on their service', function () {
    [$service, $owner] = createReviewService();
    $customer = User::factory()->create();

    $review = Review::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'rating' => 4,
        'review' => 'Nice',
    ]);

    $this->actingAs($owner, 'api')->getJson('/api/admin/review/show/'.$review->id)
        ->assertOk()
        ->assertJsonPath('data.id', $review->id);
});

test('stranger cannot show a review they do not own', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create();
    $stranger = User::factory()->create();

    $review = Review::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'rating' => 5,
    ]);

    $this->actingAs($stranger, 'api')->getJson('/api/admin/review/show/'.$review->id)
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

// -------------------------------------------------------------------------
// Order integration
// -------------------------------------------------------------------------

test('order index marks completed unreviewed order as reviewable', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create(['type' => 0]);
    createCompletedOrderForReview($customer, $service);

    $this->actingAs($customer, 'api')->getJson('/api/admin/order/index')
        ->assertOk()
        ->assertJsonPath('data.0.can_review', true)
        ->assertJsonPath('data.0.review_id', null);
});

test('order show includes review eligibility', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create(['type' => 0]);
    $order = createCompletedOrderForReview($customer, $service);

    $this->actingAs($customer, 'api')->getJson('/api/admin/order/show/'.$order->id)
        ->assertOk()
        ->assertJsonPath('data.order_details.can_review', true)
        ->assertJsonPath('data.order_details.review_id', null);
});

test('order index returns null review when not yet reviewed', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create(['type' => 0]);
    createCompletedOrderForReview($customer, $service);

    $this->actingAs($customer, 'api')->getJson('/api/admin/order/index')
        ->assertOk()
        ->assertJsonPath('data.0.review', null);
});

test('order index includes review object with provider reply', function () {
    [$service, $owner] = createReviewService();
    $customer = User::factory()->create(['type' => 0]);
    $order = createCompletedOrderForReview($customer, $service);

    $review = Review::create([
        'user_id' => $customer->id,
        'order_id' => $order->id,
        'service_id' => $service->id,
        'rating' => 5,
        'review' => 'Loved it',
        'reply' => 'Thank you!',
    ]);

    $this->actingAs($customer, 'api')->getJson('/api/admin/order/index')
        ->assertOk()
        ->assertJsonPath('data.0.review.id', $review->id)
        ->assertJsonPath('data.0.review.rating', 5)
        ->assertJsonPath('data.0.review.review', 'Loved it')
        ->assertJsonPath('data.0.review.reply', 'Thank you!')
        ->assertJsonPath('data.0.review.has_replied', true);
});

test('order show includes review object when reviewed', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create(['type' => 0]);
    $order = createCompletedOrderForReview($customer, $service);

    $review = Review::create([
        'user_id' => $customer->id,
        'order_id' => $order->id,
        'service_id' => $service->id,
        'rating' => 4,
        'review' => 'Nice work',
    ]);

    $this->actingAs($customer, 'api')->getJson('/api/admin/order/show/'.$order->id)
        ->assertOk()
        ->assertJsonPath('data.order_details.review.id', $review->id)
        ->assertJsonPath('data.order_details.review.rating', 4)
        ->assertJsonPath('data.order_details.review.has_replied', false)
        ->assertJsonPath('data.order_details.review.reply', null);
});

test('order index marks reviewed order as not reviewable', function () {
    [$service] = createReviewService();
    $customer = User::factory()->create(['type' => 0]);
    $order = createCompletedOrderForReview($customer, $service);

    $review = Review::create([
        'user_id' => $customer->id,
        'order_id' => $order->id,
        'service_id' => $service->id,
        'rating' => 5,
    ]);

    $this->actingAs($customer, 'api')->getJson('/api/admin/order/index')
        ->assertOk()
        ->assertJsonPath('data.0.can_review', false)
        ->assertJsonPath('data.0.review_id', $review->id);
});

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

/**
 * @return array{0: Service, 1: User}
 */
function createReviewService(): array
{
    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Photography',
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $owner = User::factory()->create([
        'type' => 2,
        'category_id' => $categoryId,
    ]);

    $service = Service::create([
        'title' => 'Wedding Photography',
        'user_id' => $owner->id,
        'category_id' => $categoryId,
        'location' => 'Austin',
        'description' => 'Full day coverage',
    ]);

    return [$service, $owner];
}

function createCompletedOrderForReview(User $customer, Service $service, string $status = 'completed'): Order
{
    $pricingId = DB::table('service_pricings')->insertGetId([
        'service_id' => $service->id,
        'service_type' => 'basic',
        'duration' => '4 hours',
        'price' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $orderId = DB::table('orders')->insertGetId([
        'service_id' => $service->id,
        'service_pricing_id' => $pricingId,
        'user_id' => $customer->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'phone' => '1234567890',
        'event_name' => 'Wedding',
        'event_start_date' => now()->toDateString(),
        'event_end_date' => now()->addDay()->toDateString(),
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('provider_payments')->insert([
        'user_id' => $service->user_id,
        'order_id' => $orderId,
        'amount' => 100,
        'payment_method' => 'stripe',
        'status' => 'successful',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Order::findOrFail($orderId);
}
