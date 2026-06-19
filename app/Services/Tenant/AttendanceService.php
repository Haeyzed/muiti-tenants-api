<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\AttendanceStatus;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Manages staff attendance clock in/out.
 */
class AttendanceService
{
    /**
     * Paginate the attendance records.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Attendance>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Attendance::query()
            ->with('staff')
            ->latest('clock_in_at');

        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('clock_in_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('clock_in_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Clock in a staff member.
     *
     * @param Staff $staff
     * @param string|null $notes
     * @return Attendance
     *
     * @throws RuntimeException|Throwable
     */
    public function clockIn(Staff $staff, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($staff, $notes): Attendance {
            $openAttendance = $staff->attendances()
                ->where('status', AttendanceStatus::Open)
                ->first();

            if ($openAttendance !== null) {
                throw new RuntimeException('Staff member already has an open attendance record.');
            }

            return $staff->attendances()->create([
                'clock_in_at' => now(),
                'status' => AttendanceStatus::Open,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Clock out a staff member.
     *
     * @param Staff $staff
     * @param string|null $notes
     * @return Attendance
     * @throws Throwable
     */
    public function clockOut(Staff $staff, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($staff, $notes): Attendance {
            $attendance = $staff->attendances()
                ->where('status', AttendanceStatus::Open)
                ->latest('clock_in_at')
                ->firstOrFail();

            $clockOutAt = now();
            $workedMinutes = (int)$attendance->clock_in_at->diffInMinutes($clockOutAt);

            $attendance->update([
                'clock_out_at' => $clockOutAt,
                'worked_minutes' => $workedMinutes,
                'status' => AttendanceStatus::Closed,
                'notes' => $notes ?? $attendance->notes,
            ]);

            return $attendance->fresh();
        });
    }

    /**
     * Get the open attendance record for a staff member.
     *
     * @param Staff $staff
     * @return Attendance|null
     */
    public function getOpenAttendance(Staff $staff): ?Attendance
    {
        return $staff->attendances()
            ->where('status', AttendanceStatus::Open)
            ->latest('clock_in_at')
            ->first();
    }
}
