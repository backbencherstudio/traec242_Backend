<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ServiceFaqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => 'testing-jwt-secret']);
        Mail::fake();
    }

    public function test_provider_can_create_service_with_multiple_faqs(): void
    {
        [$provider, $categoryId] = $this->createProvider();

        $response = $this->actingAs($provider, 'api')->postJson(
            '/api/provider/services/store',
            $this->servicePayload($categoryId, [
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
    }

    public function test_provider_can_create_service_without_faqs(): void
    {
        [$provider, $categoryId] = $this->createProvider();

        $response = $this->actingAs($provider, 'api')->postJson(
            '/api/provider/services/store',
            $this->servicePayload($categoryId)
        );

        $response->assertOk()
            ->assertJsonCount(0, 'data.faqs');

        $this->assertDatabaseCount('service_faqs', 0);
    }

    public function test_faq_question_and_answer_are_required(): void
    {
        [$provider, $categoryId] = $this->createProvider();

        $response = $this->actingAs($provider, 'api')->postJson(
            '/api/provider/services/store',
            $this->servicePayload($categoryId, [
                ['question' => 'Missing answer?'],
            ])
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['faqs.0.answer']);
    }

    public function test_public_show_returns_service_with_faqs(): void
    {
        [$provider, $categoryId] = $this->createProvider();

        $serviceId = $this->actingAs($provider, 'api')->postJson(
            '/api/provider/services/store',
            $this->servicePayload($categoryId, [
                ['question' => 'Do you travel?', 'answer' => 'Yes, nationwide.'],
            ])
        )->json('data.id');

        Service::find($serviceId)->update(['status' => 1]);

        $response = $this->getJson('/api/services/'.$serviceId);

        $response->assertOk()
            ->assertJsonPath('data.id', $serviceId)
            ->assertJsonPath('data.faqs.0.question', 'Do you travel?');
    }

    public function test_provider_show_returns_own_service_with_faqs(): void
    {
        [$provider, $categoryId] = $this->createProvider();

        $serviceId = $this->actingAs($provider, 'api')->postJson(
            '/api/provider/services/store',
            $this->servicePayload($categoryId, [
                ['question' => 'Deposit required?', 'answer' => 'A 20% deposit is required.'],
            ])
        )->json('data.id');

        $response = $this->actingAs($provider, 'api')->getJson('/api/provider/services/'.$serviceId);

        $response->assertOk()
            ->assertJsonPath('data.id', $serviceId)
            ->assertJsonPath('data.faqs.0.answer', 'A 20% deposit is required.');
    }

    public function test_provider_cannot_show_another_providers_service(): void
    {
        [$owner, $categoryId] = $this->createProvider();
        [$otherProvider] = $this->createProvider();

        $serviceId = $this->actingAs($owner, 'api')->postJson(
            '/api/provider/services/store',
            $this->servicePayload($categoryId)
        )->json('data.id');

        $this->actingAs($otherProvider, 'api')
            ->getJson('/api/provider/services/'.$serviceId)
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createProvider(): array
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
    private function servicePayload(int $categoryId, array $faqs = []): array
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
}
