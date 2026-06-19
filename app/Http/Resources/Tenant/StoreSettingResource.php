<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StoreSetting
 */
class StoreSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'store_name' => $this->store_name,
            'store_description' => $this->store_description,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'currency_code' => $this->currency_code,
            'timezone' => $this->timezone,
            'language_code' => $this->language_code,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
