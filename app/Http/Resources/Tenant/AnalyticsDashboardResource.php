<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array<string, mixed>
 */
class AnalyticsDashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'period' => $data['period'] ?? null,
            'traffic' => $data['traffic'] ?? null,
            'drops' => $data['drops'] ?? [],
            'conversion' => $data['conversion'] ?? [],
        ];
    }
}
