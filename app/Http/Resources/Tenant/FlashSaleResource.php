<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FlashSale
 */
class FlashSaleResource extends JsonResource
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
            'description' => $this->description,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status?->value,
            'is_active' => $this->is_active,
            'is_live' => $this->isLive(),
            'products_count' => $this->whenCounted('products'),
            'products' => FlashSaleProductResource::collection($this->whenLoaded('products')),
            'checkout_queue' => $this->whenLoaded('checkoutQueue'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
