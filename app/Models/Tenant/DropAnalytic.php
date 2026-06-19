<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aggregated performance metrics for a flash sale drop.
 */
class DropAnalytic extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'flash_sale_id',
        'queue_entries',
        'checkouts_completed',
        'revenue',
        'units_sold',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'queue_entries' => 'integer',
            'checkouts_completed' => 'integer',
            'revenue' => 'decimal:2',
            'units_sold' => 'integer',
        ];
    }

    /**
     * Get the flash sale associated with these metrics.
     *
     * @return BelongsTo<FlashSale, $this>
     */
    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }
}
