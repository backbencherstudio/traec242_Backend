<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AllServiceShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => 'testing-jwt-secret']);
        Mail::fake();
    }

    public function test_service_show_returns_reviews_with_reply_when_present(): void
    {
        [$service, $owner] = $this->createService();
        $customer = User::factory()->create(['name' => 'Jane', 'last_name' => 'Doe']);

        $review = Review::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'rating' => 5,
            'review' => 'Loved it',
            'reply' => 'Thank you!',
        ]);

        $this->getJson('/api/services/'.$service->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.reviews')
            ->assertJsonPath('data.reviews.0.id', $review->id)
            ->assertJsonPath('data.reviews.0.reviewer_name', 'Jane Doe')
            ->assertJsonPath('data.reviews.0.rating', 5)
            ->assertJsonPath('data.reviews.0.review', 'Loved it')
            ->assertJsonPath('data.reviews.0.reply', 'Thank you!')
            ->assertJsonPath('data.reviews.0.has_replied', true);
    }

    public function test_service_show_returns_null_reviews_when_none(): void
    {
        [$service] = $this->createService();

        $this->getJson('/api/services/'.$service->id)
            ->assertOk()
            ->assertJsonPath('data.reviews', null);
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
            'status' => 1,
        ]);

        return [$service, $owner];
    }
}
