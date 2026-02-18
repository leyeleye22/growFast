<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage_opportunities',
            'run_scraper',
            'manage_subscriptions',
            'view_opportunities',
        ];

        foreach (['api', 'web'] as $guard) {
            foreach ($permissions as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
            }
        }

        foreach (['api', 'web'] as $guard) {
            $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
            $admin->syncPermissions($permissions);

            $startup = Role::firstOrCreate(['name' => 'startup', 'guard_name' => $guard]);
            $startup->syncPermissions(['view_opportunities']);
        }
    }
}
