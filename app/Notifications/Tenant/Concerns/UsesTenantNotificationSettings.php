<?php

declare(strict_types=1);

namespace App\Notifications\Tenant\Concerns;

use App\Models\Tenant\NotificationSetting;

/**
 * Resolves delivery channels from tenant notification settings.
 */
trait UsesTenantNotificationSettings
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return NotificationSetting::singleton()->channelsFor(
            $this->notificationEvent(),
            $this->isAdminAlert(),
        );
    }
}
