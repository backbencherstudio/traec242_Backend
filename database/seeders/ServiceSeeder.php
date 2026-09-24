<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServicePricing;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'first_name' => 'Customer',
                'last_name' => 'Provider',
                'password' => bcrypt('12345678'),
                'status' => 1,
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole(RoleSeeder::ROLE_PROVIDER);

        $service = Service::updateOrCreate(
            ['title' => 'Event Photography'],
            [
                'user_id' => $user->id,
                'category_id' => 1,
                'location' => 'Dhaka',
                'description' => 'Professional event photography services.',
                'status' => 1,
            ],
        );

        ServicePricing::updateOrCreate(
            ['service_id' => $service->id],
            [
                'service_type' => 'standard',
                'duration' => '5 Hours',
                'price' => 500.00,
                'description' => 'Standard event photography package',
                'features' => json_encode(['Professional photographer', 'Edited photos', 'Online gallery']),
            ],
        );
    }
}
