<?php

declare(strict_types=1);

namespace App\Policies\Central;

use App\Models\Central\CentralUser;
use App\Models\Central\Plan;

/**
 * Authorization rules for subscription plan management.
 */
class PlanPolicy
{
    public function viewAny(CentralUser $user): bool
    {
        return $user->can('plans.view') || $user->can('billing.view');
    }

    public function view(CentralUser $user, Plan $plan): bool
    {
        return $this->viewAny($user);
    }

    public function create(CentralUser $user): bool
    {
        return $user->can('plans.manage');
    }

    public function update(CentralUser $user, Plan $plan): bool
    {
        return $user->can('plans.manage');
    }

    public function delete(CentralUser $user, Plan $plan): bool
    {
        return $user->can('plans.manage');
    }
}
