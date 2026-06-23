<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product relation (related, cross-sell, up-sell).
 *
 * @property int $id
 * @property int $product_id
 * @property int $related_product_id
 * @property string $relation_type
 * @property int|null $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Product $relatedProduct
 */
class ProductRelation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'related_product_id',
        'relation_type',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the source product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the related product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }
}
