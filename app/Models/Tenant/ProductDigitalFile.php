<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Digital file attachment for a digital product.
 *
 * @property int $id
 * @property int $product_id
 * @property int $media_id
 * @property string $file_name
 * @property int $download_count
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Media $media
 */
class ProductDigitalFile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'media_id',
        'file_name',
        'download_count',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'download_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the product this file belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the media file.
     *
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
