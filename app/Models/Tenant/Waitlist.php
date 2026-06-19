<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\WaitlistType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Waitlist for back-in-stock or flash sale alerts.
 */
class Waitlist extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'flash_sale_id',
        'type',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WaitlistType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<FlashSale, $this>
     */
    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    /**
     * @return HasMany<WaitlistSubscriber, $this>
     */
    public function subscribers(): HasMany
    {
        return $this->hasMany(WaitlistSubscriber::class);
    }
}
