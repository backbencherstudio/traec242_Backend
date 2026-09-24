<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            ContentSeeder::class,
            FaqSedder::class,
            CategorySeeder::class,
            PlanSeeder::class,
            StripeSeeder::class,
            ServiceSeeder::class,
            MonthlyOrderSeeder::class,
        ]);
    }
}
