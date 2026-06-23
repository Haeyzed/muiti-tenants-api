<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Manual product collection for curated displays.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int|null $image_media_id
 * @property bool $is_visible
 * @property int|null $sort_order
 * @property string|null $condition_type
 * @property array<string, mixed>|null $conditions
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Collection<int, Product> $products
 * @property-read Media|null $imageMedia
 */
class ProductCollection extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'image_media_id',
        'is_visible',
        'sort_order',
        'condition_type',
        'conditions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'conditions' => 'array',
        ];
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
     * Get the products in this collection.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_collection_items')
            ->withTimestamps()
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Get the image media.
     *
     * @return BelongsTo<Media, $this>
     */
    public function imageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * Scope a query to filter collections.
     *
     * @param  Builder<ProductCollection>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<ProductCollection>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), function (Builder $q) use ($filters): void {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            })
            ->when(isset($filters['is_visible']), function (Builder $q) use ($filters): void {
                $q->where('is_visible', (bool) $filters['is_visible']);
            });
    }
}
