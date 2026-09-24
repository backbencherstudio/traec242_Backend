<?php

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('category seeder creates expected categories without duplicates', function (): void {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    $this->assertDatabaseCount('categories', 4);

    $this->assertDatabaseHas('categories', [
        'name' => 'Photography',
        'status' => 1,
    ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Catering',
        'status' => 1,
    ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Event Planning',
        'status' => 1,
    ]);

    $category = Category::query()
        ->where('name', 'Photography')
        ->firstOrFail();

    expect($category->description)
        ->toBe('Professional photography services for events, portraits, and commercial shoots.');
});
