<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for customer management.
 */
class CustomerPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(TenantUser $user, Customer $customer): bool
    {
        return $user->can('customers.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(TenantUser $user, Customer $customer): bool
    {
        return $user->can('customers.update');
    }

    public function delete(TenantUser $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }
}
