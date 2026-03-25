<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run()
    {

        $superAdminRole = Role::updateOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'api']
        );

        $adminRole = Role::updateOrCreate(
            ['name' => 'Admin', 'guard_name' => 'api']
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'super@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('12345678'),
                'type' => 1,
                'status' => 1,
                'image' => null,
                'jwt_token' => null,
            ]
        );

        $superAdmin->syncRoles([$superAdminRole]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
                'type' => 1,
                'status' => 1,
                'image' => null,
                'jwt_token' => null,
            ]
        );

        $admin->syncRoles([$adminRole]);

        $this->command->info('Super Admin and Admin created successfully!');
    }
}
