<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\PayrollProfile;
use App\Models\Tenant\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages payroll profiles for staff members.
 */
class PayrollProfileService
{
    /**
     * Find a payroll profile for a staff member.
     *
     * @param Staff $staff
     * @return PayrollProfile|null
     */
    public function findForStaff(Staff $staff): ?PayrollProfile
    {
        return $staff->payrollProfile;
    }

    /**
     * Upsert a payroll profile.
     *
     * @param Staff $staff
     * @param array<string, mixed> $data
     * @return PayrollProfile
     * @throws Throwable
     */
    public function upsert(Staff $staff, array $data): PayrollProfile
    {
        return DB::transaction(function () use ($staff, $data): Model {
            return $staff->payrollProfile()->updateOrCreate(
                ['staff_id' => $staff->id],
                $data,
            );
        });
    }

    /**
     * Delete a payroll profile.
     *
     * @param PayrollProfile $profile
     * @return void
     */
    public function delete(PayrollProfile $profile): void
    {
        $profile->delete();
    }

    /**
     * Delete multiple payroll profiles by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return PayrollProfile::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a payroll profile permanently.
     *
     * @param PayrollProfile $profile
     * @return void
     */
    public function forceDelete(PayrollProfile $profile): void
    {
        $profile->forceDelete();
    }

    /**
     * Restore a soft-deleted payroll profile.
     *
     * @param PayrollProfile $profile
     * @return PayrollProfile
     */
    public function restore(PayrollProfile $profile): PayrollProfile
    {
        $profile->restore();

        return $profile->fresh();
    }

    /**
     * Restore multiple soft-deleted payroll profiles by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return PayrollProfile::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
