<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds central platform roles and permissions.
 */
class CentralRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',
            'tenants.activate',
            'tenants.suspend',
            'users.view',
            'users.manage',
            'billing.view',
            'billing.manage',
            'plans.view',
            'plans.manage',
        ];

        $permissionModels = collect($permissions)
            ->mapWithKeys(fn (string $name): array => [
                $name => Permission::findOrCreate($name, 'web'),
            ]);

        $rolePermissions = [
            'super-admin' => $permissions,
            'platform-admin' => [
                'tenants.view', 'tenants.create', 'tenants.update', 'tenants.activate', 'tenants.suspend',
                'users.view', 'users.manage',
            ],
            'support-agent' => ['tenants.view', 'users.view'],
            'billing-manager' => ['tenants.view', 'billing.view', 'billing.manage', 'plans.view', 'plans.manage'],
        ];

        foreach ($rolePermissions as $roleName => $rolePerms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(
                collect($rolePerms)->map(fn (string $name) => $permissionModels[$name])->all()
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
