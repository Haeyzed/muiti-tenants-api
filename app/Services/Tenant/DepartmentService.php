<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Manages organizational departments.
 */
class DepartmentService
{
    /**
     * Paginate the departments.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Department>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Department::query()
            ->filter($filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a department by ID.
     *
     * @param int $id
     * @return Department
     */
    public function find(int $id): Department
    {
        return Department::query()
            ->with(['positions', 'staff'])
            ->findOrFail($id);
    }

    /**
     * Create a new department.
     *
     * @param array<string, mixed> $data
     * @return Department
     */
    public function create(array $data): Department
    {
        return Department::query()->create($data);
    }

    /**
     * Update an existing department.
     *
     * @param Department $department
     * @param array<string, mixed> $data
     * @return Department
     */
    public function update(Department $department, array $data): Department
    {
        $department->update($data);

        return $department->fresh();
    }

    /**
     * Delete a department.
     *
     * @param Department $department
     * @return void
     */
    public function delete(Department $department): void
    {
        $department->delete();
    }

    /**
     * Delete multiple departments by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Department::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a department permanently.
     *
     * @param Department $department
     * @return void
     */
    public function forceDelete(Department $department): void
    {
        $department->forceDelete();
    }

    /**
     * Restore a soft-deleted department.
     *
     * @param Department $department
     * @return Department
     */
    public function restore(Department $department): Department
    {
        $department->restore();

        return $department->fresh();
    }

    /**
     * Restore multiple soft-deleted departments by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Department::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
