<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\BrandFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Product brand within a tenant store.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_visible
 * @property int|null $logo_media_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $website_url
 * @property int|null $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Product> $products
 * @property-read Media|null $logoMedia
 * @method static Builder<Brand>|Brand query()
 * @method static Builder<Brand>|Brand filter(array $filters)
 */
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_visible',
        'logo_media_id',
        'meta_title',
        'meta_description',
        'website_url',
        'sort_order',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return BrandFactory
     */
    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
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
     * Get the products associated with the brand.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Logo media file for this brand.
     *
     * @return BelongsTo<Media, $this>
     */
    public function logoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    /**
     * Scope a query to filter brands.
     *
     * @param  Builder<Brand>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Brand>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), function (Builder $q) use ($filters): void {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            })
            ->when(!empty($filters['is_visible']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['is_visible'])
                    ? $filters['is_visible']
                    : explode(',', (string) $filters['is_visible']);

                $booleans = [];

                if (in_array('visible', $statuses, true)) {
                    $booleans[] = true;
                }

                if (in_array('hidden', $statuses, true)) {
                    $booleans[] = false;
                }

                if (!empty($booleans)) {
                    $q->whereIn('is_visible', $booleans);
                }
            });
    }
}
