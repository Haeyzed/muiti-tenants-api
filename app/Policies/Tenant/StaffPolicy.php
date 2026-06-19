<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Staff;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for staff management.
 */
class StaffPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('staff.view');
    }

    public function view(TenantUser $user, Staff $staff): bool
    {
        return $user->can('staff.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('staff.create');
    }

    public function update(TenantUser $user, Staff $staff): bool
    {
        return $user->can('staff.update');
    }

    public function delete(TenantUser $user, Staff $staff): bool
    {
        return $user->can('staff.delete');
    }
}
