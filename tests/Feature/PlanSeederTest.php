<?php

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('plan seeder creates expected plans without duplicates', function () {
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

    expect($monthlyPlan->features)->toBe([
        'Featured provider listing',
        'Priority provider visibility',
        'Monthly subscription billing',
    ]);
});
