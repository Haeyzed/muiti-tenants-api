<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Service provider assignment for service products.
 *
 * @property int $id
 * @property int $product_id
 * @property int $provider_id
 * @property bool $is_primary
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Product $product
 * @property-read TenantUser $provider
 */
class ProductServiceProvider extends Model
{
    protected $table = 'product_service_providers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'provider_id',
        'is_primary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the service product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the provider (staff member).
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'provider_id');
    }
}
