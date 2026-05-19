<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->categories() as $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     description: string,
     *     image: string|null,
     *     status: int
     * }>
     */
    protected function categories(): array
    {
        return [
            [
                'name' => 'Photography',
                'description' => 'Professional photography services for events, portraits, and commercial shoots.',
                'image' => null,
                'status' => 1,
            ],
            [
                'name' => 'Catering',
                'description' => 'Catering providers for weddings, corporate functions, and private events.',
                'image' => null,
                'status' => 1,
            ],
            [
                'name' => 'Event Planning',
                'description' => 'Experienced planners to organize timelines, vendors, and event execution.',
                'image' => null,
                'status' => 1,
            ],
            [
                'name' => 'Decoration',
                'description' => 'Decor specialists for weddings, parties, and branded event environments.',
                'image' => null,
                'status' => 1,
            ],
        ];
    }
}
