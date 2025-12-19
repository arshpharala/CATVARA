<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auth\Module;
use App\Models\Auth\Permission;

class AuthenticationSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * MODULE → PERMISSIONS MATRIX (GLOBAL)
         */
        $matrix = [
            'users' => [
                'name' => 'User Management',
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'roles' => [
                'name' => 'Roles',
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'permissions' => [
                'name' => 'Permissions',
                'permissions' => ['view', 'assign'],
            ],
            'company' => [
                'name' => 'Company',
                'permissions' => ['view', 'edit'],
            ],
            'inventory' => [
                'name' => 'Inventory',
                'permissions' => ['view', 'adjust', 'transfer'],
            ],
            'orders' => [
                'name' => 'Orders',
                'permissions' => ['view', 'create', 'cancel'],
            ],
            'pos' => [
                'name' => 'POS',
                'permissions' => ['access'],
            ],
            'reports' => [
                'name' => 'Reports',
                'permissions' => ['view'],
            ],
        ];

        foreach ($matrix as $moduleSlug => $data) {

            $module = Module::updateOrCreate(
                ['slug' => $moduleSlug],
                [
                    'name' => $data['name'],
                    'is_active' => true,
                ]
            );

            foreach ($data['permissions'] as $permission) {
                Permission::updateOrCreate(
                    ['slug' => "{$moduleSlug}.{$permission}"],
                    [
                        'name' => ucfirst($permission) . ' ' . ucfirst($moduleSlug),
                        'module_id' => $module->id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
