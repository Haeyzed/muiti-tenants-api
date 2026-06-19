<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Department;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for departments.
 */
class DepartmentPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('staff.view');
    }

    public function view(TenantUser $user, Department $department): bool
    {
        return $user->can('staff.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('staff.create');
    }

    public function update(TenantUser $user, Department $department): bool
    {
        return $user->can('staff.update');
    }

    public function delete(TenantUser $user, Department $department): bool
    {
        return $user->can('staff.delete');
    }
}
