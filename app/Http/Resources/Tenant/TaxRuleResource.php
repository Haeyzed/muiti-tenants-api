<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\TaxRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaxRule
 */
class TaxRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_region_id' => $this->tax_region_id,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'region' => $this->whenLoaded('taxRegion', fn () => new TaxRegionResource($this->taxRegion)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
