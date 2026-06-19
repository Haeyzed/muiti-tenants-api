<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaxRate
 */
class TaxRateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_class_id' => $this->tax_class_id,
            'name' => $this->name,
            'rate' => $this->rate,
            'type' => $this->type?->value,
            'is_compound' => $this->is_compound,
            'is_active' => $this->is_active,
            'rules' => $this->whenLoaded('rules', fn () => TaxRuleResource::collection($this->rules)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
