<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\OnboardingProgress;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for onboarding.
 */
class OnboardingPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('onboarding.view');
    }

    public function update(TenantUser $user, OnboardingProgress $progress): bool
    {
        return $user->can('onboarding.manage');
    }
}
