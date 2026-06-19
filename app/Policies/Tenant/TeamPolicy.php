<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for team member management.
 */
class TeamPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('team.view');
    }

    public function view(TenantUser $user, TenantUser $member): bool
    {
        return $user->can('team.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('team.create');
    }

    public function update(TenantUser $user, TenantUser $member): bool
    {
        return $user->can('team.update');
    }

    public function delete(TenantUser $user, TenantUser $member): bool
    {
        return $user->can('team.delete') && $user->id !== $member->id;
    }

    public function suspend(TenantUser $user, TenantUser $member): bool
    {
        return $user->can('team.suspend') && $user->id !== $member->id;
    }
}
