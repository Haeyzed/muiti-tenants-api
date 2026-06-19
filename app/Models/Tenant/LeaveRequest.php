<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\LeaveRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leave request submitted by a staff member.
 */
class LeaveRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => LeaveRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the staff member who submitted the request.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the type of leave requested.
     *
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the user who reviewed the request.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'reviewed_by');
    }

    /**
     * Scope a query to filter leave requests by status.
     *
     * @param  Builder<LeaveRequest>  $query
     * @param  string|LeaveRequestStatus  $status
     * @return Builder<LeaveRequest>
     */
    public function scopeSearch(Builder $query, string|LeaveRequestStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
