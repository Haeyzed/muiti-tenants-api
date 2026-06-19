<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TeamInvitation;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for team invitations.
 */
class TeamInvitationPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('team.invite');
    }

    public function view(TenantUser $user, TeamInvitation $invitation): bool
    {
        return $user->can('team.invite');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('team.invite');
    }

    public function update(TenantUser $user, TeamInvitation $invitation): bool
    {
        return $user->can('team.invite');
    }

    public function delete(TenantUser $user, TeamInvitation $invitation): bool
    {
        return $user->can('team.invite');
    }
}
