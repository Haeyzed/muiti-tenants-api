<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\CategoryFactory;
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
 * Product category for organizing catalog items.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int|null $parent_id
 * @property bool $is_visible
 * @property int $sort_order
 * @property int|null $image_media_id
 * @property string|null $banner_media_id
 * @property string|null $color
 * @property string|null $icon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 * @property-read Collection<int, Product> $products
 * @property-read Media|null $imageMedia
 * @property-read Media|null $bannerMedia
 * @method static Builder<Category>|Category query()
 * @method static Builder<Category>|Category filter(array $filters)
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
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
        'parent_id',
        'is_visible',
        'sort_order',
        'image_media_id',
        'banner_media_id',
        'color',
        'icon',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return CategoryFactory
     */
    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
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
     * Get the parent category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the products in this category.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Image media file for this category.
     *
     * @return BelongsTo<Media, $this>
     */
    public function imageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * Banner media file for this category.
     *
     * @return BelongsTo<Media, $this>
     */
    public function bannerMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'banner_media_id');
    }

    /**
     * Scope a query to filter categories.
     *
     * @param  Builder<Category>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Category>
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
            })
            ->when(!empty($filters['parent_id']), function (Builder $q) use ($filters): void {
                $q->where('parent_id', $filters['parent_id']);
            })
            ->when(isset($filters['has_products']), function (Builder $q): void {
                $q->has('products');
            });
    }

    /**
     * Get all ancestor categories.
     *
     * @return Collection<int, Category>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection();
        $current = $this->parent;

        while ($current !== null) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Get full breadcrumb path as array.
     *
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function breadcrumbPath(): array
    {
        $path = [];
        foreach ($this->ancestors() as $ancestor) {
            $path[] = ['id' => $ancestor->id, 'name' => $ancestor->name, 'slug' => $ancestor->slug];
        }
        $path[] = ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug];

        return $path;
    }
}
