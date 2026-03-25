<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionGroups = [
            'dashboard' => [
                'admin.dashboard.index',
                'admin.dashboard.create',
                'admin.dashboard.store',
                'admin.dashboard.update',
            ],
            'admin' => [
                'admin.index',
                'admin.register',
                'admin.store',
                'admin.edit',
                'admin.update',
                'admin.destroy',
                'admin.logout',
                'admin.password',
                'admin.passwordchange',
            ],
            'role' => [
                'admin.role.index',
                'admin.role.store',
                'admin.role.edit',
                'admin.role.update',
                'admin.role.delete',
            ],
            'permission' => [
                'admin.permission.index',
                'admin.permission.store',
                'admin.permission.edit',
                'admin.permission.update',
                'admin.permission.destroy',
            ],
            'category' => [
                'admin.category.index',
                'admin.category.store',
                'admin.category.edit',
                'admin.category.update',
                'admin.category.destroy',
            ],
            'subcategory' => [
                'admin.subcategory.index',
                'admin.subcategory.store',
                'admin.subcategory.edit',
                'admin.subcategory.update',
                'admin.subcategory.destroy',
            ],
            'brand' => [
                'admin.brand.index',
                'admin.brand.store',
                'admin.brand.edit',
                'admin.brand.update',
                'admin.brand.destroy',
            ],
            'setting' => [
                'admin.setting.index',
                'admin.setting.update',
            ],
            'slider' => [
                'admin.slider.index',
                'admin.slider.store',
                'admin.slider.edit',
                'admin.slider.update',
                'admin.slider.destroy',

            ],
            'faq-categories' => [
                'admin.faq-categories.index',
                'admin.faq-categories.store',
                'admin.faq-categories.edit',
                'admin.faq-categories.update',
                'admin.faq-categories.destroy',

            ],
            'faq' => [
                'admin.faq.index',
                'admin.faq.store',
                'admin.faq.edit',
                'admin.faq.update',
                'admin.faq.destroy',

            ],
            'hire' => [
                'admin.hire.index',
                'admin.hire.store',
                'admin.hire.edit',
                'admin.hire.update',
                'admin.hire.destroy',

            ],
            'about' => [
                'admin.about.index',
                'admin.about.update',
            ],

        ];

        foreach ($permissionGroups as $group => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::updateOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'admin'],
                    ['group_name' => $group]
                );
            }
        }

        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        $this->command->info('Permissions with groups seeded successfully!');
    }
}
