<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rule linking a tax rate to a geographic region.
 */
class TaxRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'tax_rate_id',
        'tax_region_id',
        'applies_to',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * @return BelongsTo<TaxRegion, $this>
     */
    public function taxRegion(): BelongsTo
    {
        return $this->belongsTo(TaxRegion::class);
    }
}
