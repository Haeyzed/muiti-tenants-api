<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product type resource for dropdowns and references.
 */
class ProductTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this['value'] ?? null,
            'label' => $this['label'] ?? null,
            'description' => $this['description'] ?? null,
        ];
    }
}
