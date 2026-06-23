<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * YouTube video attached to a product.
 *
 * @property int $id
 * @property int $product_id
 * @property string $video_url
 * @property string $video_id
 * @property string|null $title
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_primary
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Product $product
 */
class ProductVideo extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'video_url',
        'video_id',
        'title',
        'description',
        'sort_order',
        'is_primary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the product this video belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the embed URL for the video.
     */
    public function embedUrl(): string
    {
        return "https://www.youtube.com/embed/{$this->video_id}";
    }

    /**
     * Get the thumbnail URL.
     */
    public function thumbnailUrl(): string
    {
        return "https://img.youtube.com/vi/{$this->video_id}/hqdefault.jpg";
    }

    /**
     * Get the watch URL.
     */
    public function watchUrl(): string
    {
        return "https://www.youtube.com/watch?v={$this->video_id}";
    }
}
