<?php

namespace Tests\Feature;

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

    public function test_customer_can_store_a_review(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
            'service_id' => $service->id,
            'review' => 'Great work!',
            'rating' => 5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'rating' => 5,
            'review' => 'Great work!',
        ]);
    }

    public function test_user_cannot_review_their_own_service(): void
    {
        [$service, $owner] = $this->createService();

        $this->actingAs($owner, 'api')->postJson('/api/admin/review/store', [
            'service_id' => $service->id,
            'rating' => 4,
        ])->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_review_the_same_service_twice(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create();

        Review::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'rating' => 5,
        ]);

        $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
            'service_id' => $service->id,
            'rating' => 3,
        ])->assertStatus(409);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_store_validates_rating_range(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create();

        $this->actingAs($customer, 'api')->postJson('/api/admin/review/store', [
            'service_id' => $service->id,
            'rating' => 6,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
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

    public function test_customer_only_sees_their_own_reviews_in_index(): void
    {
        [$service] = $this->createService();
        $customer = User::factory()->create();
        $other = User::factory()->create();

        Review::create(['user_id' => $customer->id, 'service_id' => $service->id, 'rating' => 5]);
        Review::create(['user_id' => $other->id, 'service_id' => $service->id, 'rating' => 4]);

        $this->actingAs($customer, 'api')->getJson('/api/admin/review/index')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $customer->id);
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
}
