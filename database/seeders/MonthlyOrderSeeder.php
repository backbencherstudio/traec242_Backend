<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\Service;
use App\Models\ServicePricing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MonthlyOrderSeeder extends Seeder
{
    public function run(): void
    {
        $service = Service::firstOrFail();
        $pricing = ServicePricing::where('service_id', $service->id)->firstOrFail();
        $customer = User::where('email', 'customer@example.com')->firstOrFail();
        $provider = User::where('email', 'admin@gmail.com')->firstOrFail();

        for ($i = 11; $i >= 0; $i--) {

            $date = Carbon::now()->subMonths($i);

            // Create Order
            $order = Order::create([
                'service_id' => $service->id,
                'service_pricing_id' => $pricing->id,
                'user_id' => $customer->id,

                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '01712345678',

                'address' => 'Dhaka',
                'city' => 'Dhaka',
                'state' => 'Dhaka',
                'zip_code' => '1207',

                'event_name' => $date->format('F Y').' Event',
                'event_description' => 'Monthly dummy event data',

                'guest_count' => rand(50, 200),
                'event_duration' => '5 Hours',

                'event_start_date' => $date->toDateString(),
                'event_end_date' => $date->toDateString(),

                'start_time' => '10:00',
                'end_time' => '15:00',

                'question_one' => null,
                'question_two' => null,
                'question_three' => null,
                'question_four' => null,
                'question_five' => null,
                'question_six' => null,

                'include_order_ids' => json_encode([]),

                'agree_terms' => 1,
                'payment_method' => 'stripe',
                'status' => 'confirmed',

                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Amount generate
            $amount = rand(500, 5000);

            // Provider Payment create
            ProviderPayment::create([
                'order_id' => $order->id,
                'user_id' => $provider->id,

                'transaction_id' => 'txn_'.uniqid(),

                'amount' => $amount,
                'admin_commission_amount' => $amount * 0.20,
                'provider_amount' => $amount * 0.80,

                'currency' => 'USD',
                'payment_method' => 'stripe_checkout',
                'status' => 'successful',

                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }
}
