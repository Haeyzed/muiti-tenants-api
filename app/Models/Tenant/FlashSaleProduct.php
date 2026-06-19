<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product attached to a flash sale with drop pricing and stock limits.
 */
class FlashSaleProduct extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'product_variant_id',
        'sale_price',
        'stock_limit',
        'sold_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'stock_limit' => 'integer',
            'sold_count' => 'integer',
        ];
    }

    /**
     * Get the flash sale this product is attached to.
     *
     * @return BelongsTo<FlashSale, $this>
     */
    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    /**
     * Get the product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the specific variant of the product.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the remaining stock limit for this product in the flash sale.
     *
     * @return int|null
     */
    public function remainingStock(): ?int
    {
        if ($this->stock_limit === null) {
            return null;
        }

        return max(0, $this->stock_limit - $this->sold_count);
    }

    /**
     * Determine if the product is sold out in the context of the flash sale.
     *
     * @return bool
     */
    public function isSoldOut(): bool
    {
        return $this->stock_limit !== null && $this->remainingStock() === 0;
    }
}
