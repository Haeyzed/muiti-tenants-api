<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Geographic region for tax rule application.
 */
class TaxRegion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'country_code',
        'state_code',
        'postal_code_pattern',
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
     * @return HasMany<TaxRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(TaxRule::class);
    }
}
