<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array<string, mixed>
 */
class DropAnalyticResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'flash_sale_id' => $data['flash_sale_id'] ?? null,
            'drop' => $data['drop'] ?? null,
            'conversion_trend' => $data['conversion_trend'] ?? [],
        ];
    }
}
