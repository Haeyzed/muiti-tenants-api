<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Business rule applied to a flash sale drop.
 */
class FlashSaleRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'flash_sale_id',
        'rule_type',
        'rule_value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rule_value' => 'array',
        ];
    }

    /**
     * Get the flash sale this rule belongs to.
     *
     * @return BelongsTo<FlashSale, $this>
     */
    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }
}
