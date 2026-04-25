<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the seeded plans are created only once.
     */
    public function test_plan_seeder_creates_expected_plans_without_duplicates(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->assertDatabaseCount('plans', 1);

        $this->assertDatabaseHas('plans', [
            'name' => 'Provider Plan',
            'package' => 'monthly',
            'price' => 100,
            'day' => 30,
            'stripe_price_id' => 'price_test_provider_monthly',
            'status' => 1,
        ]);

        $monthlyPlan = Plan::query()
            ->where('name', 'Provider Plan')
            ->where('package', 'monthly')
            ->firstOrFail();

        $this->assertSame(
            [
                'Featured provider listing',
                'Priority provider visibility',
                'Monthly subscription billing',
            ],
            $monthlyPlan->features
        );
    }
}
