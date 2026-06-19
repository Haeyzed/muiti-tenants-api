<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Shift;
use App\Models\Tenant\ShiftAssignment;
use App\Models\Tenant\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages work shifts and shift assignments.
 */
class ShiftService
{
    /**
     * Paginate shifts.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Shift>
     */
    public function paginateShifts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Shift::query()->latest();

        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a shift by ID.
     *
     * @param int $id
     * @return Shift
     */
    public function findShift(int $id): Shift
    {
        return Shift::query()->findOrFail($id);
    }

    /**
     * Create a new shift.
     *
     * @param array<string, mixed> $data
     * @return Shift
     */
    public function createShift(array $data): Shift
    {
        return Shift::query()->create($data);
    }

    /**
     * Update an existing shift.
     *
     * @param Shift $shift
     * @param array<string, mixed> $data
     * @return Shift
     */
    public function updateShift(Shift $shift, array $data): Shift
    {
        $shift->update($data);

        return $shift->fresh();
    }

    /**
     * Delete a shift.
     *
     * @param Shift $shift
     * @return void
     */
    public function deleteShift(Shift $shift): void
    {
        $shift->delete();
    }

    /**
     * Paginate shift assignments.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, ShiftAssignment>
     */
    public function paginateAssignments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ShiftAssignment::query()
            ->with(['staff', 'shift'])
            ->latest('scheduled_date');

        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (!empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('scheduled_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('scheduled_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Assign a shift to a staff member.
     *
     * @param Staff $staff
     * @param array<string, mixed> $data
     * @return ShiftAssignment
     * @throws Throwable
     */
    public function assign(Staff $staff, array $data): ShiftAssignment
    {
        return DB::transaction(function () use ($staff, $data): ShiftAssignment {
            return $staff->shiftAssignments()->create($data);
        });
    }

    /**
     * Update a shift assignment.
     *
     * @param ShiftAssignment $assignment
     * @param array<string, mixed> $data
     * @return ShiftAssignment
     */
    public function updateAssignment(ShiftAssignment $assignment, array $data): ShiftAssignment
    {
        $assignment->update($data);

        return $assignment->fresh(['staff', 'shift']);
    }

    /**
     * Delete a shift assignment.
     *
     * @param ShiftAssignment $assignment
     * @return void
     */
    public function deleteAssignment(ShiftAssignment $assignment): void
    {
        $assignment->delete();
    }
}
