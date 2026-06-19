<?php

declare(strict_types=1);

namespace App\Http\Resources\Central;

use App\Models\Central\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Domain
 */
class DomainResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'full_domain' => $this->full_domain,
            'is_primary' => $this->is_primary,
            'verification_status' => $this->verification_status?->value,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
