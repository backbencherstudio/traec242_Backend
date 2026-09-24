<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public const string ROLE_ADMIN = 'admin';

    public const string ROLE_PROVIDER = 'provider';

    public const string ROLE_USER = 'user';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [self::ROLE_ADMIN, self::ROLE_PROVIDER, self::ROLE_USER];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'api',
            ]);
        }
    }
}
