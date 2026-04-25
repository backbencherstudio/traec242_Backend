<?php

namespace Tests\Feature;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the seeded categories are created only once.
     */
    public function test_category_seeder_creates_expected_categories_without_duplicates(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseCount('categories', 4);

        $this->assertDatabaseHas('categories', [
            'name' => 'Photography',
            'slug' => 'photography',
            'status' => 1,
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Catering',
            'slug' => 'catering',
            'status' => 1,
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Event Planning',
            'slug' => 'event-planning',
            'status' => 1,
        ]);

        $category = Category::query()
            ->where('slug', 'photography')
            ->firstOrFail();

        $this->assertSame(
            'Professional photography services for events, portraits, and commercial shoots.',
            $category->description
        );
    }
}
