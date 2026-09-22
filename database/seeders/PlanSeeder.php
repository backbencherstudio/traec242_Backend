<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->plans() as $plan) {
            Plan::query()->updateOrCreate(
                [
                    'name' => $plan['name'],
                    'package' => $plan['package'],
                ],
                $plan
            );
        }
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     title: string,
     *     price: int,
     *     currency: string,
     *     package: string,
     *     day: int,
     *     features: array<int, string>,
     *     stripe_product_id: string|null,
     *     stripe_price_id: string|null,
     *     status: bool
     * }>
     */
    protected function plans(): array
    {
        return [
            [
                'name' => 'Provider Plan',
                'title' => 'Provider Monthly Plan',
                'price' => 100,
                'currency' => 'USD',
                'package' => 'monthly',
                'day' => 30,
                'features' => [
                    'Featured provider listing',
                    'Priority provider visibility',
                    'Monthly subscription billing',
                ],
                'stripe_product_id' => config('services.stripe.provider_product_id') ?: env('STRIPE_PROVIDER_PRODUCT_ID', 'prod_test_provider'),
                'stripe_price_id' => config('services.stripe.provider_price_id') ?: env('STRIPE_PROVIDER_PRICE_ID', 'price_test_provider_monthly'),
                'status' => true,
            ],
        ];
    }
}
