<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for HR module operations.
 */
class HrPolicy
{
    public function view(TenantUser $user): bool
    {
        return $user->can('hr.view');
    }

    public function manage(TenantUser $user): bool
    {
        return $user->can('hr.manage');
    }
}
