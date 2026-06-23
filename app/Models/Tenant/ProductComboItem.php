<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Individual item within a combo/bundle product.
 *
 * @property int $id
 * @property int $product_id
 * @property int $included_product_id
 * @property int|null $included_variant_id
 * @property int $quantity
 * @property bool $is_optional
 * @property float|null $discount_percentage
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Product $includedProduct
 * @property-read ProductVariant|null $includedVariant
 */
class ProductComboItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'included_product_id',
        'included_variant_id',
        'quantity',
        'is_optional',
        'discount_percentage',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_optional' => 'boolean',
            'discount_percentage' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the combo product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the included product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function includedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'included_product_id');
    }

    /**
     * Get the included variant.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function includedVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'included_variant_id');
    }
}
