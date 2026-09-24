<?php

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

test('provider can create service with multiple faqs', function () {
    [$provider, $categoryId] = createFaqProvider();

    $response = $this->actingAs($provider, 'api')->postJson(
        '/api/provider/services/store',
        faqServicePayload($categoryId, [
            ['question' => 'Do you travel?', 'answer' => 'Yes, nationwide.'],
            ['question' => 'Deposit required?', 'answer' => 'A 20% deposit is required.'],
        ])
    );

    $response->assertOk()
        ->assertJsonPath('data.faqs.0.question', 'Do you travel?')
        ->assertJsonCount(2, 'data.faqs');

    $serviceId = $response->json('data.id');

    $this->assertDatabaseCount('service_faqs', 2);
    $this->assertDatabaseHas('service_faqs', [
        'service_id' => $serviceId,
        'question' => 'Deposit required?',
        'answer' => 'A 20% deposit is required.',
    ]);
});

test('provider can create service without faqs', function () {
    [$provider, $categoryId] = createFaqProvider();

    $response = $this->actingAs($provider, 'api')->postJson(
        '/api/provider/services/store',
        faqServicePayload($categoryId)
    );

    $response->assertOk()
        ->assertJsonCount(0, 'data.faqs');

    $this->assertDatabaseCount('service_faqs', 0);
});

test('faq question and answer are required', function () {
    [$provider, $categoryId] = createFaqProvider();

    $response = $this->actingAs($provider, 'api')->postJson(
        '/api/provider/services/store',
        faqServicePayload($categoryId, [
            ['question' => 'Missing answer?'],
        ])
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['faqs.0.answer']);
});

test('public show returns service with faqs', function () {
    [$provider, $categoryId] = createFaqProvider();

    $serviceId = $this->actingAs($provider, 'api')->postJson(
        '/api/provider/services/store',
        faqServicePayload($categoryId, [
            ['question' => 'Do you travel?', 'answer' => 'Yes, nationwide.'],
        ])
    )->json('data.id');

    Service::find($serviceId)->update(['status' => 1]);

    $response = $this->getJson('/api/services/'.$serviceId);

    $response->assertOk()
        ->assertJsonPath('data.id', $serviceId)
        ->assertJsonPath('data.faqs.0.question', 'Do you travel?');
});

test('provider show returns own service with faqs', function () {
    [$provider, $categoryId] = createFaqProvider();

    $serviceId = $this->actingAs($provider, 'api')->postJson(
        '/api/provider/services/store',
        faqServicePayload($categoryId, [
            ['question' => 'Deposit required?', 'answer' => 'A 20% deposit is required.'],
        ])
    )->json('data.id');

    $response = $this->actingAs($provider, 'api')->getJson('/api/provider/services/'.$serviceId);

    $response->assertOk()
        ->assertJsonPath('data.id', $serviceId)
        ->assertJsonPath('data.faqs.0.answer', 'A 20% deposit is required.');
});

test('provider cannot show another providers service', function () {
    [$owner, $categoryId] = createFaqProvider();
    [$otherProvider] = createFaqProvider();

    $serviceId = $this->actingAs($owner, 'api')->postJson(
        '/api/provider/services/store',
        faqServicePayload($categoryId)
    )->json('data.id');

    $this->actingAs($otherProvider, 'api')
        ->getJson('/api/provider/services/'.$serviceId)
        ->assertNotFound();
});

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

/**
 * @return array{0: User, 1: int}
 */
function createFaqProvider(): array
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

    return [$provider, $categoryId];
}

/**
 * @param  array<int, array<string, string>>  $faqs
 * @return array<string, mixed>
 */
function faqServicePayload(int $categoryId, array $faqs = []): array
{
    return [
        'title' => 'Wedding Photography',
        'category_id' => $categoryId,
        'location' => 'Austin',
        'description' => 'Full day coverage',
        'pricings' => [
            ['service_type' => 'basic', 'duration' => '4 hours', 'price' => 100],
            ['service_type' => 'standard', 'duration' => '6 hours', 'price' => 200],
            ['service_type' => 'premium', 'duration' => '8 hours', 'price' => 300],
        ],
        'faqs' => $faqs,
    ];
}
