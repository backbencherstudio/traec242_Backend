<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => 'testing-jwt-secret']);
        Mail::fake();
    }

    public function test_customer_can_store_a_review_for_a_completed_order(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create();
        $order = $this->createCompletedOrder($customer, $service);

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
    }

    public function test_customer_cannot_review_another_users_order(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->createCompletedOrder($customer, $service);

        $this->actingAs($stranger, 'api')->postJson('/api/admin/review/store', [
            'order_id' => $order->id,
            'rating' => 4,
        ])->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_cannot_review_an_order_that_is_not_completed(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create();
        $order = $this->createCompletedOrder($customer, $service, 'pending');

        $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
            'order_id' => $order->id,
            'rating' => 5,
        ])->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_cannot_review_the_same_order_twice(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create();
        $order = $this->createCompletedOrder($customer, $service);

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
    }

    public function test_store_requires_an_order_id_and_valid_rating(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
            'rating' => 6,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['order_id', 'rating']);
    }

    public function test_service_owner_can_reply_to_a_review_and_it_persists(): void
    {
        [$service, $owner] = $this->createService();
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
    }

    public function test_non_owner_cannot_reply_to_a_review(): void
    {
        [$service] = $this->createService();
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
    }

    public function test_provider_sees_only_reviews_on_their_own_services_with_reply_status(): void
    {
        [$service, $owner] = $this->createService();
        [$otherService] = $this->createService();
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
    }

    public function test_provider_received_review_shows_replied_after_responding(): void
    {
        [$service, $owner] = $this->createService();
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
    }

    public function test_review_author_can_show_their_own_review(): void
    {
        [$service] = $this->createService();
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
    }

    public function test_service_owner_can_show_a_review_on_their_service(): void
    {
        [$service, $owner] = $this->createService();
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
    }

    public function test_stranger_cannot_show_a_review_they_do_not_own(): void
    {
        [$service] = $this->createService();
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
    }

    public function test_order_index_marks_completed_unreviewed_order_as_reviewable(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create(['type' => 0]);
        $this->createCompletedOrder($customer, $service);

        $this->actingAs($customer, 'api')->getJson('/api/admin/order/index')
            ->assertOk()
            ->assertJsonPath('data.0.can_review', true)
            ->assertJsonPath('data.0.review_id', null);
    }

    public function test_order_show_includes_review_eligibility(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create(['type' => 0]);
        $order = $this->createCompletedOrder($customer, $service);

        $this->actingAs($customer, 'api')->getJson('/api/admin/order/show/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.order_details.can_review', true)
            ->assertJsonPath('data.order_details.review_id', null);
    }

    public function test_order_index_marks_reviewed_order_as_not_reviewable(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create(['type' => 0]);
        $order = $this->createCompletedOrder($customer, $service);

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
    }

    /**
     * @return array{0: Service, 1: User}
     */
    private function createService(): array
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

    private function createCompletedOrder(User $customer, Service $service, string $status = 'completed'): Order
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
}
