<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\TenantUser;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Dispatches tenant notifications to users and on-demand recipients.
 */
class NotificationDispatchService
{
    public function notifyUser(?TenantUser $user, Notification $notification): void
    {
        if ($user === null) {
            return;
        }

        $user->notify($notification);
    }

    public function notifyMail(string $email, Notification $notification): void
    {
        NotificationFacade::route('mail', $email)->notify($notification);
    }

    /**
     * @param  Collection<int, TenantUser>|iterable<int, TenantUser>  $users
     */
    public function notifyUsers(iterable $users, Notification $notification): void
    {
        foreach ($users as $user) {
            $this->notifyUser($user, $notification);
        }
    }

    /**
     * @return Collection<int, TenantUser>
     */
    public function staffWithPermission(string $permission): Collection
    {
        return TenantUser::query()
            ->permission($permission)
            ->where('is_active', true)
            ->get();
    }
}
