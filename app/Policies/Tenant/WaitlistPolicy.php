<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TenantUser;
use App\Models\Tenant\WaitlistSubscriber;

/**
 * Authorization rules for waitlist management.
 */
class WaitlistPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('waitlists.view');
    }

    public function join(TenantUser $user): bool
    {
        return $user->can('waitlists.join');
    }

    public function unsubscribe(TenantUser $user, WaitlistSubscriber $subscriber): bool
    {
        return $user->can('waitlists.join')
            && ($subscriber->customer_id === $user->customer?->id || $user->can('waitlists.manage'));
    }
}
