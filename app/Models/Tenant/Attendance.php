<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\AttendanceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance record for a staff member clock in/out.
 */
class Attendance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'clock_in_at',
        'clock_out_at',
        'worked_minutes',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'worked_minutes' => 'integer',
            'status' => AttendanceStatus::class,
        ];
    }

    /**
     * Get the staff member associated with the attendance record.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Scope a query to filter attendances by status.
     *
     * @param  Builder<Attendance>  $query
     * @param  string|AttendanceStatus  $status
     * @return Builder<Attendance>
     */
    public function scopeSearch(Builder $query, string|AttendanceStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
