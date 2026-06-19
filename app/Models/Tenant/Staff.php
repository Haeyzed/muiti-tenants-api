<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\EmploymentStatus;
use App\Enums\Tenant\EmploymentType;
use Database\Factories\Tenant\StaffFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Staff member within a tenant organization.
 */
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'staff';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'department_id',
        'position_id',
        'staff_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employment_type',
        'employment_status',
        'hire_date',
        'termination_date',
        'allow_login',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return StaffFactory
     */
    protected static function newFactory(): StaffFactory
    {
        return StaffFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'employment_status' => EmploymentStatus::class,
            'hire_date' => 'date',
            'termination_date' => 'date',
            'allow_login' => 'boolean',
        ];
    }

    /**
     * Get the options for activity logging.
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name', 'last_name', 'email', 'department_id',
                'position_id', 'employment_type', 'employment_status', 'allow_login',
            ])
            ->logOnlyDirty();
    }

    /**
     * Get the user account associated with the staff member.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'user_id');
    }

    /**
     * Get the department the staff member belongs to.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position held by the staff member.
     *
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the staff member's profile.
     *
     * @return HasOne<EmployeeProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    /**
     * Get the emergency contacts for the staff member.
     *
     * @return HasMany<EmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /**
     * Get the documents associated with the staff member.
     *
     * @return HasMany<StaffDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    /**
     * Get the leave requests submitted by the staff member.
     *
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get the shift assignments for the staff member.
     *
     * @return HasMany<ShiftAssignment, $this>
     */
    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /**
     * Get the attendance records for the staff member.
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the payroll profile for the staff member.
     *
     * @return HasOne<PayrollProfile, $this>
     */
    public function payrollProfile(): HasOne
    {
        return $this->hasOne(PayrollProfile::class);
    }

    /**
     * Get the full name of the staff member.
     *
     * @return string
     */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Scope a query to search staff members.
     *
     * @param  Builder<Staff>  $query
     * @param  string  $search
     * @return Builder<Staff>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('staff_id', 'like', "%{$search}%");
        });
    }
}
