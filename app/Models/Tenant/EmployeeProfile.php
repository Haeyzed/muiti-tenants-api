<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extended personal profile for a staff member.
 */
class EmployeeProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'date_of_birth',
        'gender',
        'nationality',
        'marital_status',
        'bio',
        'employment_history',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'employment_history' => 'array',
        ];
    }

    /**
     * Get the staff member associated with the profile.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
