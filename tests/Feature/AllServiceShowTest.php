<?php

use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['jwt.secret' => 'testing-jwt-secret']);
    Mail::fake();
});

test('service show returns reviews with reply when present', function (): void {
    [$service, $owner] = createServiceForShow();
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
});

test('service show returns null reviews when none', function (): void {
    [$service] = createServiceForShow();

    $this->getJson('/api/services/'.$service->id)
        ->assertOk()
        ->assertJsonPath('data.reviews', null);
});

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

/**
 * @return array{0: Service, 1: User}
 */
function createServiceForShow(): array
{
    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Photography',
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $owner = createProviderUser([
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
