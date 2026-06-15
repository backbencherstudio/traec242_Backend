<?php

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
            PermisionSeeder::class,
            ContentSeeder::class,
            FaqSedder::class,
            CategorySeeder::class,
            PlanSeeder::class,
            StripeSeeder::class,
            MonthlyOrderSeeder::class,
        ]);
    }
}
