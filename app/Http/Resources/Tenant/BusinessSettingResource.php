<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessSetting
 */
class BusinessSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'business_name' => $this->business_name,
            'registration_number' => $this->registration_number,
            'business_type' => $this->business_type,
            'business_email' => $this->business_email,
            'business_phone' => $this->business_phone,
            'website' => $this->website,
            'support_email' => $this->support_email,
            'support_phone' => $this->support_phone,
            'country_code' => $this->country_code,
            'state_code' => $this->state_code,
            'city_id' => $this->city_id,
            'postal_code' => $this->postal_code,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
