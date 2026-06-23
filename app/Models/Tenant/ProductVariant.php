<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Variant option for a product (size, color, etc.).
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $sku
 * @property float $price
 * @property float|null $compare_at_price
 * @property float|null $cost_price
 * @property array<string, mixed>|null $options
 * @property bool $is_default
 * @property int|null $image_media_id
 * @property string|null $barcode
 * @property float|null $weight
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Product $product
 * @property-read Inventory|null $inventory
 * @property-read Media|null $imageMedia
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductPricingTier> $pricingTiers
 */
class ProductVariant extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'options',
        'is_default',
        'image_media_id',
        'barcode',
        'weight',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'options' => 'array',
            'is_default' => 'boolean',
            'weight' => 'decimal:3',
        ];
    }

    /**
     * Get the product that this variant belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the inventory for this product variant.
     *
     * @return HasOne<Inventory, $this>
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_variant_id');
    }

    /**
     * Get the variant image media.
     *
     * @return BelongsTo<Media, $this>
     */
    public function imageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * Get pricing tiers for this variant.
     *
     * @return HasMany<ProductPricingTier, $this>
     */
    public function pricingTiers(): HasMany
    {
        return $this->hasMany(ProductPricingTier::class, 'variant_id');
    }

    /**
     * Scope a query to search variants by name or sku.
     *
     * @param  Builder<ProductVariant>  $query
     * @param  string  $search
     * @return Builder<ProductVariant>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    /**
     * Calculate discount percentage.
     *
     * @return float|null
     */
    public function discountPercentage(): ?float
    {
        if ($this->compare_at_price === null || $this->compare_at_price <= 0) {
            return null;
        }

        return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100, 2);
    }

    /**
     * Check if variant is on sale.
     *
     * @return bool
     */
    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }
}
