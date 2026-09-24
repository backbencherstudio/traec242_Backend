<?php

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('public services index lists active services with pagination', function (): void {
    $provider = User::factory()->create(['type' => 2]);
    $category = Category::create(['name' => 'Music', 'status' => 1]);

    Service::create([
        'title' => 'DJ Performance',
        'user_id' => $provider->id,
        'category_id' => $category->id,
        'description' => 'Live DJ set',
        'location' => 'New York',
        'status' => 1,
    ]);

    Service::create([
        'title' => 'Inactive Service',
        'user_id' => $provider->id,
        'category_id' => $category->id,
        'description' => 'Draft service',
        'location' => 'New York',
        'status' => 0,
    ]);

    $response = $this->getJson('/api/services');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'DJ Performance');
});

test('public services index filters by search query and category', function (): void {
    $provider = User::factory()->create(['type' => 2]);
    $cat1 = Category::create(['name' => 'Catering', 'status' => 1]);
    $cat2 = Category::create(['name' => 'Decor', 'status' => 1]);

    Service::create([
        'title' => 'Gourmet Catering',
        'user_id' => $provider->id,
        'category_id' => $cat1->id,
        'status' => 1,
    ]);

    Service::create([
        'title' => 'Wedding Floral Decor',
        'user_id' => $provider->id,
        'category_id' => $cat2->id,
        'status' => 1,
    ]);

    $searchResponse = $this->getJson('/api/services?search=Floral');
    $searchResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Wedding Floral Decor');

    $catResponse = $this->getJson('/api/services?category=Catering');
    $catResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Gourmet Catering');
});

test('public services index filters by max price', function (): void {
    $provider = User::factory()->create(['type' => 2]);
    $cat = Category::create(['name' => 'General', 'status' => 1]);

    $affordableService = Service::create([
        'title' => 'Affordable Service',
        'user_id' => $provider->id,
        'category_id' => $cat->id,
        'status' => 1,
    ]);

    DB::table('service_pricings')->insert([
        'service_id' => $affordableService->id,
        'service_type' => 'basic',
        'duration' => '2 hours',
        'price' => 50,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $expensiveService = Service::create([
        'title' => 'Luxury Service',
        'user_id' => $provider->id,
        'category_id' => $cat->id,
        'status' => 1,
    ]);

    DB::table('service_pricings')->insert([
        'service_id' => $expensiveService->id,
        'service_type' => 'premium',
        'duration' => '8 hours',
        'price' => 500,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/services?max_price=100');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Affordable Service');
});

test('public service show returns service details', function (): void {
    $provider = User::factory()->create(['type' => 2, 'name' => 'Chef', 'last_name' => 'Gordon']);
    $cat = Category::create(['name' => 'Catering', 'status' => 1]);

    $service = Service::create([
        'title' => 'Private Dining',
        'user_id' => $provider->id,
        'category_id' => $cat->id,
        'description' => 'A unique dining experience',
        'status' => 1,
    ]);

    $response = $this->getJson("/api/services/{$service->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $service->id)
        ->assertJsonPath('data.title', 'Private Dining')
        ->assertJsonPath('data.provider_name', 'Chef Gordon');
});
