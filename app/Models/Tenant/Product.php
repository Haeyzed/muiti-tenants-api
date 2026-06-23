<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\ProductType;
use Database\Factories\Tenant\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Sellable product in a tenant flash-sale store.
 *
 * @property int $id
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $short_description
 * @property string $sku
 * @property float $price
 * @property float|null $compare_at_price
 * @property float|null $cost_price
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property bool $is_visible
 * @property bool $is_featured
 * @property string $product_type
 * @property int|null $download_limit
 * @property int|null $download_expiry_days
 * @property int|null $preview_media_id
 * @property int|null $duration_minutes
 * @property int|null $buffer_minutes
 * @property int|null $max_participants
 * @property string|null $location_type
 * @property string|null $service_location
 * @property bool $allow_partial_combo
 * @property string|null $youtube_url
 * @property string|null $tax_class_id
 * @property float $weight
 * @property float|null $length
 * @property float|null $width
 * @property float|null $height
 * @property string|null $weight_unit
 * @property string|null $dimension_unit
 * @property string|null $barcode
 * @property string|null $mpn
 * @property string|null $gtin
 * @property int|null $primary_image_media_id
 * @property string|null $seo_slug
 * @property string|null $canonical_url
 * @property int $view_count
 * @property float $average_rating
 * @property int $review_count
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $category
 * @property-read Brand|null $brand
 * @property-read Collection<int, Tag> $tags
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read Collection<int, ProductAttributeValue> $attributeValues
 * @property-read Collection<int, ProductReview> $reviews
 * @property-read Collection<int, ProductRelation> $relatedProducts
 * @property-read Collection<int, ProductRelation> $crossSellProducts
 * @property-read Collection<int, ProductRelation> $upSellProducts
 * @property-read Inventory|null $inventory
 * @property-read Collection<int, Inventory> $inventories
 * @property-read Collection<int, ProductImage> $productImages
 * @property-read Media|null $primaryImageMedia
 * @property-read Collection<int, ProductDigitalFile> $digitalFiles
 * @property-read Collection<int, ProductComboItem> $comboItems
 * @property-read Collection<int, ProductServiceProvider> $serviceProviders
 * @property-read Collection<int, ProductVideo> $videos
 * @property-read Media|null $previewMedia
 * @method static Builder<Product>|Product query()
 * @method static Builder<Product>|Product filter(array $filters)
 * @method static Builder<Product>|Product search(string $search)
 * @method static Builder<Product>|Product visible()
 * @method static Builder<Product>|Product featured()
 * @method static Builder<Product>|Product inStock()
 * @method static Builder<Product>|Product lowStock()
 * @method static Builder<Product>|Product ofType(string $type)
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_visible',
        'is_featured',
        'product_type',
        'download_limit',
        'download_expiry_days',
        'preview_media_id',
        'duration_minutes',
        'buffer_minutes',
        'max_participants',
        'location_type',
        'service_location',
        'allow_partial_combo',
        'youtube_url',
        'tax_class_id',
        'weight',
        'length',
        'width',
        'height',
        'weight_unit',
        'dimension_unit',
        'barcode',
        'mpn',
        'gtin',
        'primary_image_media_id',
        'seo_slug',
        'canonical_url',
        'published_at',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return ProductFactory
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'allow_partial_combo' => 'boolean',
            'product_type' => ProductType::class,
            'download_limit' => 'integer',
            'download_expiry_days' => 'integer',
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'max_participants' => 'integer',
            'weight' => 'decimal:3',
            'length' => 'decimal:3',
            'width' => 'decimal:3',
            'height' => 'decimal:3',
            'view_count' => 'integer',
            'average_rating' => 'decimal:2',
            'review_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the options for activity logging.
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'price', 'is_visible', 'is_featured', 'category_id', 'brand_id', 'product_type'])
            ->logOnlyDirty();
    }

    /**
     * Get the options for generating the slug.
     *
     * @return SlugOptions
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Get the category that the product belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand that the product belongs to.
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the tags associated with the product.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Get the variants for the product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the default variant for the product.
     *
     * @return HasOne<ProductVariant, $this>
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * Get the attribute values for the product.
     *
     * @return HasMany<ProductAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Get the reviews for the product.
     *
     * @return HasMany<ProductReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true);
    }

    /**
     * Get all reviews including pending.
     *
     * @return HasMany<ProductReview, $this>
     */
    public function allReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Get related products.
     *
     * @return HasMany<ProductRelation, $this>
     */
    public function relatedProducts(): HasMany
    {
        return $this->hasMany(ProductRelation::class, 'product_id')
            ->where('relation_type', 'related')
            ->with('relatedProduct');
    }

    /**
     * Get cross-sell products.
     *
     * @return HasMany<ProductRelation, $this>
     */
    public function crossSellProducts(): HasMany
    {
        return $this->hasMany(ProductRelation::class, 'product_id')
            ->where('relation_type', 'cross_sell')
            ->with('relatedProduct');
    }

    /**
     * Get up-sell products.
     *
     * @return HasMany<ProductRelation, $this>
     */
    public function upSellProducts(): HasMany
    {
        return $this->hasMany(ProductRelation::class, 'product_id')
            ->where('relation_type', 'up_sell')
            ->with('relatedProduct');
    }

    /**
     * Get product images ordered by sort order.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get the primary inventory for the product.
     *
     * @return HasOne<Inventory, $this>
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class)->whereNull('product_variant_id');
    }

    /**
     * Get all inventories for the product (including variants).
     *
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the primary image media.
     *
     * @return BelongsTo<Media, $this>
     */
    public function primaryImageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'primary_image_media_id');
    }

    /**
     * Get the collections this product belongs to.
     *
     * @return BelongsToMany<ProductCollection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class, 'product_collection_items')
            ->withTimestamps()
            ->withPivot('sort_order');
    }

    /**
     * Get product SEO data.
     *
     * @return HasOne<ProductSeo, $this>
     */
    public function seo(): HasOne
    {
        return $this->hasOne(ProductSeo::class);
    }

    /**
     * Get product pricing tiers.
     *
     * @return HasMany<ProductPricingTier, $this>
     */
    public function pricingTiers(): HasMany
    {
        return $this->hasMany(ProductPricingTier::class);
    }

    // -------------------------------------------------------------------------
    // Product Type Specific Relationships
    // -------------------------------------------------------------------------

    /**
     * Get digital files for digital products.
     *
     * @return HasMany<ProductDigitalFile, $this>
     */
    public function digitalFiles(): HasMany
    {
        return $this->hasMany(ProductDigitalFile::class)->orderBy('sort_order');
    }

    /**
     * Get combo items for combo/bundle products.
     *
     * @return HasMany<ProductComboItem, $this>
     */
    public function comboItems(): HasMany
    {
        return $this->hasMany(ProductComboItem::class)->orderBy('sort_order');
    }

    /**
     * Get service providers for service products.
     *
     * @return HasMany<ProductServiceProvider, $this>
     */
    public function serviceProviders(): HasMany
    {
        return $this->hasMany(ProductServiceProvider::class);
    }

    /**
     * Get YouTube videos for the product.
     *
     * @return HasMany<ProductVideo, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    /**
     * Get preview media for digital products.
     *
     * @return BelongsTo<Media, $this>
     */
    public function previewMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'preview_media_id');
    }

    // -------------------------------------------------------------------------
    // Computed Properties
    // -------------------------------------------------------------------------

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
     * Check if product is on sale.
     *
     * @return bool
     */
    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    /**
     * Get profit margin.
     *
     * @return float|null
     */
    public function profitMargin(): ?float
    {
        if ($this->cost_price === null || $this->cost_price <= 0) {
            return null;
        }

        return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
    }

    /**
     * Get combo total value (sum of included products).
     *
     * @return float|null
     */
    public function comboTotalValue(): ?float
    {
        if ($this->product_type !== ProductType::Combo) {
            return null;
        }

        return $this->comboItems->sum(function (ProductComboItem $item): float {
            $productPrice = $item->includedProduct?->price ?? 0;
            $discount = $item->discount_percentage ?? 0;
            $discountedPrice = $productPrice * (1 - $discount / 100);

            return $discountedPrice * $item->quantity;
        });
    }

    /**
     * Get combo savings amount.
     *
     * @return float|null
     */
    public function comboSavings(): ?float
    {
        $totalValue = $this->comboTotalValue();

        if ($totalValue === null) {
            return null;
        }

        return max(0, $totalValue - (float) $this->price);
    }

    /**
     * Check if product requires shipping.
     *
     * @return bool
     */
    public function requiresShipping(): bool
    {
        $type = ProductType::tryFrom($this->product_type);

        return $type?->requiresShipping() ?? true;
    }

    /**
     * Check if product tracks inventory.
     *
     * @return bool
     */
    public function tracksInventory(): bool
    {
        $type = ProductType::tryFrom($this->product_type);

        return $type?->tracksInventory() ?? true;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope a query to search products by name, sku, or description.
     *
     * @param  Builder<Product>  $query
     * @param  string  $search
     * @return Builder<Product>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('meta_keywords', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter products.
     *
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), function (Builder $q) use ($filters) {
                $q->search((string) $filters['search']);
            })
            ->when(!empty($filters['category_id']), function (Builder $q) use ($filters) {
                if (is_array($filters['category_id'])) {
                    $q->whereIn('category_id', $filters['category_id']);
                } else {
                    $q->where('category_id', $filters['category_id']);
                }
            })
            ->when(!empty($filters['brand_id']), function (Builder $q) use ($filters) {
                if (is_array($filters['brand_id'])) {
                    $q->whereIn('brand_id', $filters['brand_id']);
                } else {
                    $q->where('brand_id', $filters['brand_id']);
                }
            })
            ->when(isset($filters['is_visible']), function (Builder $q) use ($filters) {
                $q->where('is_visible', (bool) $filters['is_visible']);
            })
            ->when(isset($filters['is_featured']), function (Builder $q) use ($filters) {
                $q->where('is_featured', (bool) $filters['is_featured']);
            })
            ->when(!empty($filters['product_type']), function (Builder $q) use ($filters) {
                $types = is_array($filters['product_type']) ? $filters['product_type'] : [$filters['product_type']];
                $q->whereIn('product_type', $types);
            })
            ->when(!empty($filters['min_price']), function (Builder $q) use ($filters) {
                $q->where('price', '>=', (float) $filters['min_price']);
            })
            ->when(!empty($filters['max_price']), function (Builder $q) use ($filters) {
                $q->where('price', '<=', (float) $filters['max_price']);
            })
            ->when(!empty($filters['tag_ids']), function (Builder $q) use ($filters) {
                $tagIds = is_array($filters['tag_ids']) ? $filters['tag_ids'] : explode(',', (string) $filters['tag_ids']);
                $q->whereHas('tags', function (Builder $tq) use ($tagIds) {
                    $tq->whereIn('tags.id', $tagIds);
                });
            })
            ->when(!empty($filters['attribute_values']), function (Builder $q) use ($filters) {
                $attributeValues = is_array($filters['attribute_values'])
                    ? $filters['attribute_values']
                    : explode(',', (string) $filters['attribute_values']);
                $q->whereHas('attributeValues', function (Builder $aq) use ($attributeValues) {
                    $aq->whereIn('attribute_value_id', $attributeValues);
                });
            })
            ->when(!empty($filters['in_stock']), function (Builder $q) {
                $q->whereHas('inventory', function (Builder $iq) {
                    $iq->whereRaw('quantity > reserved_quantity');
                });
            })
            ->when(!empty($filters['has_variants']), function (Builder $q) {
                $q->has('variants');
            })
            ->when(!empty($filters['created_from']), function (Builder $q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['created_from']);
            })
            ->when(!empty($filters['created_to']), function (Builder $q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['created_to']);
            });
    }

    /**
     * Scope a query to only include visible products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope a query to only include featured products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include products of a specific type.
     *
     * @param  Builder<Product>  $query
     * @param  string  $type
     * @return Builder<Product>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('product_type', $type);
    }

    /**
     * Scope a query to only include in-stock products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('inventory', function (Builder $q) {
            $q->whereRaw('quantity > reserved_quantity');
        });
    }

    /**
     * Scope a query to only include low stock products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereHas('inventory', function (Builder $q) {
            $q->whereRaw('(quantity - reserved_quantity) <= low_stock_threshold')
              ->whereRaw('quantity > 0');
        });
    }

    /**
     * Increment view count.
     *
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * Recalculate average rating from approved reviews.
     *
     * @return void
     */
    public function recalculateRating(): void
    {
        $stats = $this->reviews()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        $this->update([
            'average_rating' => $stats?->avg_rating ? round((float) $stats->avg_rating, 2) : 0,
            'review_count' => (int) ($stats?->total_reviews ?? 0),
        ]);
    }

    /**
     * Get structured data for SEO (Schema.org JSON-LD).
     *
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        $type = ProductType::tryFrom($this->product_type);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->name,
            'description' => $this->meta_description ?? $this->short_description ?? $this->description,
            'sku' => $this->sku,
            'url' => $this->canonical_url ?? route('tenant.products.show', $this->slug),
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) $this->price,
                'priceCurrency' => config('app.currency', 'USD'),
                'availability' => $this->inventory?->availableQuantity() > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];

        if ($this->brand) {
            $data['brand'] = [
                '@type' => 'Brand',
                'name' => $this->brand->name,
            ];
        }

        if ($this->primaryImageMedia) {
            $data['image'] = [$this->primaryImageMedia->getUrl()];
        }

        if ($this->average_rating > 0 && $this->review_count > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $this->average_rating,
                'reviewCount' => (string) $this->review_count,
            ];
        }

        if ($this->mpn) {
            $data['mpn'] = $this->mpn;
        }

        if ($this->gtin) {
            $data['gtin'] = $this->gtin;
        }

        // Add video data if available
        $primaryVideo = $this->videos->firstWhere('is_primary', true) ?? $this->videos->first();
        if ($primaryVideo) {
            $data['video'] = [
                '@type' => 'VideoObject',
                'name' => $primaryVideo->title ?? $this->name,
                'description' => $primaryVideo->description ?? $this->description,
                'thumbnailUrl' => $primaryVideo->thumbnailUrl(),
                'contentUrl' => $primaryVideo->watchUrl(),
                'embedUrl' => $primaryVideo->embedUrl(),
            ];
        }

        // Service-specific schema
        if ($type === ProductType::Service) {
            $data['@type'] = 'Service';
            unset($data['offers']['availability']);

            if ($this->duration_minutes) {
                $data['termsOfService'] = "Duration: {$this->duration_minutes} minutes";
            }
        }

        return $data;
    }
}
