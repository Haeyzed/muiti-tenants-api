<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\FlashSaleProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FlashSaleProduct
 */
class FlashSaleProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'sale_price' => $this->sale_price,
            'stock_limit' => $this->stock_limit,
            'sold_count' => $this->sold_count,
            'remaining_stock' => $this->remainingStock(),
            'is_sold_out' => $this->isSoldOut(),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
