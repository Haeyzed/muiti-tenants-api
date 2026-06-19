<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\LeaveRequestStatus;
use App\Events\Tenant\LeaveRequestSubmitted;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\LeaveType;
use App\Models\Tenant\Staff;
use App\Models\Tenant\TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Manages leave types and leave requests.
 */
class LeaveService
{
    /**
     * Paginate the leave requests.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, LeaveRequest>
     */
    public function paginateRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LeaveRequest::query()
            ->with(['staff', 'leaveType', 'reviewer'])
            ->latest();

        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a leave request by ID.
     *
     * @param int $id
     * @return LeaveRequest
     */
    public function findRequest(int $id): LeaveRequest
    {
        return LeaveRequest::query()
            ->with(['staff', 'leaveType', 'reviewer'])
            ->findOrFail($id);
    }

    /**
     * Create a new leave type.
     *
     * @param array<string, mixed> $data
     * @return LeaveType
     */
    public function createType(array $data): LeaveType
    {
        return LeaveType::query()->create($data);
    }

    /**
     * Update an existing leave type.
     *
     * @param LeaveType $leaveType
     * @param array<string, mixed> $data
     * @return LeaveType
     */
    public function updateType(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update($data);

        return $leaveType->fresh();
    }

    /**
     * Delete a leave type.
     *
     * @param LeaveType $leaveType
     * @return void
     */
    public function deleteType(LeaveType $leaveType): void
    {
        $leaveType->delete();
    }

    /**
     * Delete multiple leave types by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteManyTypes(array $ids): int
    {
        return LeaveType::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a leave type permanently.
     *
     * @param LeaveType $leaveType
     * @return void
     */
    public function forceDeleteType(LeaveType $leaveType): void
    {
        $leaveType->forceDelete();
    }

    /**
     * Restore a soft-deleted leave type.
     *
     * @param LeaveType $leaveType
     * @return LeaveType
     */
    public function restoreType(LeaveType $leaveType): LeaveType
    {
        $leaveType->restore();

        return $leaveType->fresh();
    }

    /**
     * Restore multiple soft-deleted leave types by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreManyTypes(array $ids): int
    {
        return LeaveType::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Submit a new leave request for a staff member.
     *
     * @param Staff $staff
     * @param array<string, mixed> $data
     * @return LeaveRequest
     * @throws Throwable
     */
    public function submitRequest(Staff $staff, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($staff, $data): LeaveRequest {
            $request = $staff->leaveRequests()->create([
                ...$data,
                'status' => LeaveRequestStatus::Pending,
            ]);

            $request = $this->findRequest($request->id);
            LeaveRequestSubmitted::dispatch($request);

            return $request;
        });
    }

    /**
     * Approve a leave request.
     *
     * @param LeaveRequest $request
     * @param TenantUser $reviewer
     * @param string|null $notes
     * @return LeaveRequest
     * @throws Throwable
     */
    public function approve(LeaveRequest $request, TenantUser $reviewer, ?string $notes = null): LeaveRequest
    {
        return $this->review($request, $reviewer, LeaveRequestStatus::Approved, $notes);
    }

    /**
     * Reject a leave request.
     *
     * @param LeaveRequest $request
     * @param TenantUser $reviewer
     * @param string|null $notes
     * @return LeaveRequest
     */
    public function reject(LeaveRequest $request, TenantUser $reviewer, ?string $notes = null): LeaveRequest
    {
        return $this->review($request, $reviewer, LeaveRequestStatus::Rejected, $notes);
    }

    /**
     * Cancel a leave request.
     *
     * @param LeaveRequest $request
     * @return LeaveRequest
     * @throws RuntimeException
     */
    public function cancel(LeaveRequest $request): LeaveRequest
    {
        if ($request->status !== LeaveRequestStatus::Pending) {
            throw new RuntimeException('Only pending leave requests can be cancelled.');
        }

        $request->update(['status' => LeaveRequestStatus::Cancelled]);

        return $request->fresh();
    }

    /**
     * Delete a leave request.
     *
     * @param LeaveRequest $request
     * @return void
     */
    public function deleteRequest(LeaveRequest $request): void
    {
        $request->delete();
    }

    /**
     * Delete multiple leave requests by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteManyRequests(array $ids): int
    {
        return LeaveRequest::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a leave request permanently.
     *
     * @param LeaveRequest $request
     * @return void
     */
    public function forceDeleteRequest(LeaveRequest $request): void
    {
        $request->forceDelete();
    }

    /**
     * Restore a soft-deleted leave request.
     *
     * @param LeaveRequest $request
     * @return LeaveRequest
     */
    public function restoreRequest(LeaveRequest $request): LeaveRequest
    {
        $request->restore();

        return $request->fresh();
    }

    /**
     * Restore multiple soft-deleted leave requests by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreManyRequests(array $ids): int
    {
        return LeaveRequest::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Review a leave request.
     *
     * @param LeaveRequest $request
     * @param TenantUser $reviewer
     * @param LeaveRequestStatus $status
     * @param string|null $notes
     * @return LeaveRequest
     * @throws RuntimeException
     * @throws Throwable
     */
    private function review(
        LeaveRequest       $request,
        TenantUser         $reviewer,
        LeaveRequestStatus $status,
        ?string            $notes,
    ): LeaveRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $status, $notes): LeaveRequest {
            if ($request->status !== LeaveRequestStatus::Pending) {
                throw new RuntimeException('Leave request has already been reviewed.');
            }

            $request->update([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            return $request->fresh(['staff', 'leaveType', 'reviewer']);
        });
    }
}
