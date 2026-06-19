<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NotificationSetting
 */
class NotificationSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'email_enabled' => $this->email_enabled,
            'sms_enabled' => $this->sms_enabled,
            'push_enabled' => $this->push_enabled,
            'admin_alerts_enabled' => $this->admin_alerts_enabled,
            'channels' => $this->channels,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
