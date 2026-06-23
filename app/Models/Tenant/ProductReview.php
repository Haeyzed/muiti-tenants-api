<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Customer review for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $customer_id
 * @property string|null $author_name
 * @property string|null $author_email
 * @property int $rating
 * @property string|null $title
 * @property string|null $content
 * @property bool $is_verified_purchase
 * @property bool $is_approved
 * @property int|null $parent_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Product $product
 * @property-read ProductReview|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductReview> $replies
 */
class ProductReview extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'customer_id',
        'author_name',
        'author_email',
        'rating',
        'title',
        'content',
        'is_verified_purchase',
        'is_approved',
        'parent_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    /**
     * Get the product this review belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the parent review (for replies).
     *
     * @return BelongsTo<ProductReview, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get replies to this review.
     *
     * @return HasMany<ProductReview, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope a query to only include approved reviews.
     *
     * @param  Builder<ProductReview>  $query
     * @return Builder<ProductReview>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include verified purchase reviews.
     *
     * @param  Builder<ProductReview>  $query
     * @return Builder<ProductReview>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified_purchase', true);
    }
}
