<?php

declare(strict_types=1);

namespace App\Http\Resources\Central;

use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status?->value,
            'plan' => $this->plan,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'primary_domain' => new DomainResource($this->whenLoaded('primaryDomain')),
            'settings' => TenantSettingResource::collection($this->whenLoaded('settings')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
