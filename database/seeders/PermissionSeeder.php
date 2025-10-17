<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view-users',
            'view-roles',
            'view-permissions',
            'view-activity-logs',
            'view-system-logs',
            'clear-system-logs',
            'create-users',
            'edit-users',
            'delete-users',
            'create-roles',
            'edit-roles',
            'delete-roles',
            'view-vendors',
            'create-vendors',
            'edit-vendors',
            'delete-vendors',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
