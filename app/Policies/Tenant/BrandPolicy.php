<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Brand;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for brand management.
 */
class BrandPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('brands.view');
    }

    public function view(TenantUser $user, Brand $brand): bool
    {
        return $user->can('brands.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('brands.create');
    }

    public function update(TenantUser $user, Brand $brand): bool
    {
        return $user->can('brands.update');
    }

    public function delete(TenantUser $user, Brand $brand): bool
    {
        return $user->can('brands.delete');
    }
}
