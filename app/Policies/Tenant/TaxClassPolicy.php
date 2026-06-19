<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TaxClass;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for tax configuration.
 */
class TaxClassPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('tax.view');
    }

    public function view(TenantUser $user, TaxClass $taxClass): bool
    {
        return $user->can('tax.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('tax.create');
    }

    public function update(TenantUser $user, TaxClass $taxClass): bool
    {
        return $user->can('tax.update');
    }

    public function delete(TenantUser $user, TaxClass $taxClass): bool
    {
        return $user->can('tax.delete');
    }

    public function calculate(TenantUser $user): bool
    {
        return $user->can('tax.calculate');
    }
}
