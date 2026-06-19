<?php

declare(strict_types=1);

namespace App\Policies\Central;

use App\Models\Central\CentralUser;
use App\Models\Central\Tenant;

/**
 * Authorization rules for central tenant management.
 */
class TenantPolicy
{
    public function viewAny(CentralUser $user): bool
    {
        return $user->can('tenants.view');
    }

    public function view(CentralUser $user, Tenant $tenant): bool
    {
        return $user->can('tenants.view');
    }

    public function create(CentralUser $user): bool
    {
        return $user->can('tenants.create');
    }

    public function update(CentralUser $user, Tenant $tenant): bool
    {
        return $user->can('tenants.update');
    }

    public function delete(CentralUser $user, Tenant $tenant): bool
    {
        return $user->can('tenants.delete');
    }

    public function activate(CentralUser $user, Tenant $tenant): bool
    {
        return $user->can('tenants.activate');
    }

    public function suspend(CentralUser $user, Tenant $tenant): bool
    {
        return $user->can('tenants.suspend');
    }

    public function manageBilling(CentralUser $user, Tenant $tenant): bool
    {
        return $user->can('billing.manage');
    }

    public function viewBilling(CentralUser $user, Tenant $tenant): bool
    {
        return $user->can('billing.view');
    }
}
