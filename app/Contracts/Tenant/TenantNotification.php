<?php

declare(strict_types=1);

namespace App\Contracts\Tenant;

use App\Enums\Tenant\NotificationEvent;

/**
 * Tenant notifications that respect store notification settings.
 */
interface TenantNotification
{
    public function notificationEvent(): NotificationEvent;

    public function isAdminAlert(): bool;
}
