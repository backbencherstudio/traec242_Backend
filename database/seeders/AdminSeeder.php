<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api']
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'super@gmail.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('12345678'),
                'status' => 1,
                'image' => null,
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->syncRoles([$adminRole]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'Admin',
                'last_name' => null,
                'password' => Hash::make('12345678'),
                'status' => 1,
                'image' => null,
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles([$adminRole]);

        $this->command->info('Super Admin and Admin created successfully!');
    }
}
