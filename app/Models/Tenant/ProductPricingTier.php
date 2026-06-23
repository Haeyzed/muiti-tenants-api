<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Volume-based pricing tier for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $min_quantity
 * @property int|null $max_quantity
 * @property float $price
 * @property string|null $customer_group_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 */
class ProductPricingTier extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'min_quantity',
        'max_quantity',
        'price',
        'customer_group_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    /**
     * Get the product this tier belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant this tier applies to.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
