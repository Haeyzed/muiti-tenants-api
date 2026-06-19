<?php

declare(strict_types=1);

namespace App\Http\Resources\Central;

use App\Models\Central\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TenantSetting
 */
class TenantSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }
}
