<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Position;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for positions.
 */
class PositionPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('staff.view');
    }

    public function view(TenantUser $user, Position $position): bool
    {
        return $user->can('staff.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('staff.create');
    }

    public function update(TenantUser $user, Position $position): bool
    {
        return $user->can('staff.update');
    }

    public function delete(TenantUser $user, Position $position): bool
    {
        return $user->can('staff.delete');
    }
}
