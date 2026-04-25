<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'premium',
            'title' => 'Provider Monthly Plan',
            'price' => 100,
            'currency' => 'USD',
            'package' => 'monthly',
            'day' => 30,
            'features' => ['Featured provider listing'],
            'stripe_product_id' => 'prod_test_provider',
            'stripe_price_id' => 'price_test_provider_monthly',
            'status' => true,
        ];
    }
}
