<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\TaxType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tax rate within a tax class.
 */
class TaxRate extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'tax_class_id',
        'name',
        'type',
        'rate',
        'is_compound',
        'is_active',
        'priority',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TaxType::class,
            'rate' => 'decimal:4',
            'is_compound' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TaxClass, $this>
     */
    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    /**
     * @return HasMany<TaxRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(TaxRule::class);
    }
}
