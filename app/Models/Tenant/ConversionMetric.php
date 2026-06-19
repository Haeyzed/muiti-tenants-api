<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily conversion metrics for flash sales.
 */
class ConversionMetric extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'flash_sale_id',
        'recorded_on',
        'visitors',
        'conversions',
        'conversion_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'visitors' => 'integer',
            'conversions' => 'integer',
            'conversion_rate' => 'decimal:4',
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
