<?php

use App\Models\Category;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('provider directory index lists providers with pagination', function (): void {
    createProviderUser([
        'name' => 'Provider One',
    ]);

    createClientUser([
        'name' => 'Regular Customer',
    ]);

    $response = $this->getJson('/api/providers');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Provider One');
});

test('provider directory filters by search keyword', function (): void {
    createProviderUser([
        'name' => 'Sammy',
        'last_name' => 'Davis',
    ]);

    createProviderUser([
        'name' => 'Frank',
        'last_name' => 'Sinatra',
    ]);

    $response = $this->getJson('/api/providers?search=Sammy');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Sammy');
});

test('provider directory show returns provider details, categories, and services', function (): void {
    $category = Category::create(['name' => 'Photography', 'status' => 1]);

    $provider = createProviderUser([
        'name' => 'Alex',
        'last_name' => 'Morgan',
        'category_id' => [$category->id],
    ]);

    Service::create([
        'title' => 'Wedding Shots',
        'user_id' => $provider->id,
        'category_id' => $category->id,
        'status' => 1,
    ]);

    $response = $this->getJson("/api/providers/{$provider->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.provider.name', 'Alex')
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.name', 'Photography')
        ->assertJsonCount(1, 'data.services')
        ->assertJsonPath('data.services.0.title', 'Wedding Shots');
});

test('provider directory show returns 404 for unknown provider', function (): void {
    $response = $this->getJson('/api/providers/99999');

    $response->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Provider not found');
});
