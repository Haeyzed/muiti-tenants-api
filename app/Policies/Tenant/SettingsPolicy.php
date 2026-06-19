<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\BusinessSetting;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for store settings.
 */
class SettingsPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('settings.view');
    }

    public function update(TenantUser $user, BusinessSetting $setting): bool
    {
        return $user->can('settings.update');
    }
}
