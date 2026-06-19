<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\StaffCreated;
use App\Models\Tenant\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Manages staff members within a tenant organization.
 */
class StaffService
{
    /**
     * Paginate staff members.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Staff>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Staff::query()
            ->with(['department', 'position'])
            ->latest();

        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('staff_id', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a staff member by ID.
     *
     * @param int $id
     * @return Staff
     */
    public function find(int $id): Staff
    {
        return Staff::query()
            ->with([
                'user', 'department', 'position', 'profile',
                'emergencyContacts', 'documents.media', 'payrollProfile',
            ])
            ->findOrFail($id);
    }

    /**
     * Create a new staff member.
     *
     * @param array<string, mixed> $data
     * @return Staff
     * @throws Throwable
     */
    public function create(array $data): Staff
    {
        return DB::transaction(function () use ($data): Staff {
            if (empty($data['staff_id'])) {
                $data['staff_id'] = $this->generateStaffId();
            }

            $staff = Staff::query()->create($data);

            $staff = $this->find($staff->id);
            StaffCreated::dispatch($staff);

            return $staff;
        });
    }

    /**
     * Update an existing staff member.
     *
     * @param Staff $staff
     * @param array<string, mixed> $data
     * @return Staff
     * @throws Throwable
     */
    public function update(Staff $staff, array $data): Staff
    {
        return DB::transaction(function () use ($staff, $data): Staff {
            $staff->update($data);

            return $this->find($staff->id);
        });
    }

    /**
     * Delete a staff member.
     *
     * @param Staff $staff
     * @return void
     */
    public function delete(Staff $staff): void
    {
        $staff->delete();
    }

    /**
     * Generate a unique staff ID.
     *
     * @return string
     */
    private function generateStaffId(): string
    {
        return 'STF-' . strtoupper(Str::random(8));
    }
}
