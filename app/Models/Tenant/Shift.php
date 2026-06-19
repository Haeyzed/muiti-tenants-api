<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Work shift definition.
 */
class Shift extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the assignments for this shift.
     *
     * @return HasMany<ShiftAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /**
     * Scope a query to search shifts by name.
     *
     * @param  Builder<Shift>  $query
     * @param  string  $search
     * @return Builder<Shift>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
