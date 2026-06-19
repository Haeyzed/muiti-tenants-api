<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds tenant store roles and permissions.
 */
class TenantRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'inventory.view', 'inventory.manage',
            'orders.view', 'orders.manage', 'orders.create',
            'flash-sales.view', 'flash-sales.create', 'flash-sales.update', 'flash-sales.delete', 'flash-sales.manage',
            'checkout.join',
            'waitlists.view', 'waitlists.join', 'waitlists.manage',
            'cart.manage',
            'payments.initiate', 'payments.view', 'payments.manage',
            'analytics.view', 'notifications.view',
            'onboarding.view', 'onboarding.manage',
            'settings.view', 'settings.update',
            'team.view', 'team.create', 'team.update', 'team.delete', 'team.invite', 'team.suspend',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete', 'customers.manage',
            'staff.view', 'staff.create', 'staff.update', 'staff.delete',
            'hr.view', 'hr.manage',
            'tax.view', 'tax.create', 'tax.update', 'tax.delete', 'tax.calculate',
        ];

        $permissionModels = collect($permissions)
            ->mapWithKeys(fn (string $name): array => [
                $name => Permission::findOrCreate($name, 'web'),
            ]);

        $allProductPerms = [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'inventory.view', 'inventory.manage',
        ];

        $flashSalePerms = [
            'flash-sales.view', 'flash-sales.create', 'flash-sales.update',
            'flash-sales.delete', 'flash-sales.manage', 'checkout.join',
        ];

        $ownerPerms = $permissions;

        $rolePermissions = [
            'tenant-owner' => $ownerPerms,
            'store-owner' => $ownerPerms,
            'store-administrator' => array_diff($ownerPerms, ['team.delete']),
            'store-manager' => [
                ...$allProductPerms, ...$flashSalePerms,
                'orders.view', 'orders.manage', 'orders.create', 'cart.manage',
                'payments.view', 'payments.manage', 'analytics.view', 'notifications.view',
                'waitlists.view', 'waitlists.manage',
                'onboarding.view', 'settings.view', 'settings.update',
                'team.view', 'team.invite',
                'customers.view', 'customers.create', 'customers.update', 'customers.manage',
                'staff.view', 'tax.view', 'tax.calculate',
            ],
            'sales-manager' => [
                'products.view', 'categories.view', 'brands.view',
                'flash-sales.view', 'orders.view', 'orders.manage', 'orders.create',
                'payments.view', 'analytics.view', 'waitlists.view',
                'customers.view', 'customers.create', 'customers.update',
            ],
            'inventory-manager' => [
                'products.view', 'inventory.view', 'inventory.manage', 'flash-sales.view',
            ],
            'hr-manager' => [
                'staff.view', 'staff.create', 'staff.update', 'staff.delete',
                'hr.view', 'hr.manage', 'team.view',
            ],
            'customer-support' => [
                'products.view', 'orders.view', 'waitlists.view', 'waitlists.manage',
                'customers.view', 'customers.update',
            ],
            'marketing-manager' => [
                'products.view', 'categories.view', 'brands.view', 'analytics.view',
                'flash-sales.view', 'settings.view',
            ],
            'finance-manager' => [
                'orders.view', 'payments.view', 'payments.manage', 'analytics.view',
                'tax.view', 'tax.create', 'tax.update', 'tax.calculate', 'settings.view',
            ],
            'staff' => [
                'products.view', 'hr.view',
            ],
            'customer' => [
                'products.view', 'categories.view', 'brands.view', 'flash-sales.view',
                'checkout.join', 'waitlists.join', 'cart.manage', 'orders.create',
                'payments.initiate', 'notifications.view',
            ],
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
