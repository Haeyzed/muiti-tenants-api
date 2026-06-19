<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\Tenant\LeaveRequestStatus;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\ClockAttendanceRequest;
use App\Http\Requests\Tenant\StoreLeaveRequestRequest;
use App\Http\Requests\Tenant\StoreLeaveTypeRequest;
use App\Http\Requests\Tenant\UpsertEmployeeProfileRequest;
use App\Http\Requests\Tenant\UpsertPayrollProfileRequest;
use App\Http\Resources\Tenant\AttendanceResource;
use App\Http\Resources\Tenant\LeaveRequestResource;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\TenantUser;
use App\Services\Tenant\AttendanceService;
use App\Services\Tenant\EmployeeService;
use App\Services\Tenant\LeaveService;
use App\Services\Tenant\PayrollProfileService;
use App\Services\Tenant\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use RuntimeException;
use Throwable;

/**
 * Manages HR operations: profiles, attendance, leave, shifts, payroll.
 */
class HrController extends ApiController
{
    public function __construct(
        private readonly EmployeeService $employeeService,
        private readonly AttendanceService $attendanceService,
        private readonly LeaveService $leaveService,
        private readonly ShiftService $shiftService,
        private readonly PayrollProfileService $payrollProfileService,
    ) {}

    /**
     * Upsert an employee profile.
     *
     * @param UpsertEmployeeProfileRequest $request
     * @param Staff $staff
     * @return JsonResponse
     * @throws Throwable
     */
    public function upsertProfile(UpsertEmployeeProfileRequest $request, Staff $staff): JsonResponse
    {
        Gate::authorize('hr.manage');

        $profile = $this->employeeService->upsertProfile($staff, $request->validated());

        return $this->success($profile, 'Employee profile saved successfully.');
    }

    /**
     * Get a paginated list of attendances.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function attendances(Request $request): JsonResponse
    {
        Gate::authorize('hr.view');

        $filters = $request->validate([
            'staff_id'  => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $attendances = $this->attendanceService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($attendances, AttendanceResource::collection($attendances), 'Attendances retrieved successfully.');
    }

    /**
     * Clock in a staff member.
     *
     * @param ClockAttendanceRequest $request
     * @param Staff $staff
     * @return JsonResponse
     * @throws Throwable
     */
    public function clockIn(ClockAttendanceRequest $request, Staff $staff): JsonResponse
    {
        Gate::authorize('hr.manage');

        try {
            $attendance = $this->attendanceService->clockIn($staff, $request->validated('notes'));
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        return $this->created(
            new AttendanceResource($attendance),
            'Clocked in successfully.',
        );
    }

    /**
     * Clock out a staff member.
     *
     * @param ClockAttendanceRequest $request
     * @param Staff $staff
     * @return JsonResponse
     * @throws Throwable
     */
    public function clockOut(ClockAttendanceRequest $request, Staff $staff): JsonResponse
    {
        Gate::authorize('hr.manage');

        try {
            $attendance = $this->attendanceService->clockOut($staff, $request->validated('notes'));
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        return $this->success(
            new AttendanceResource($attendance),
            'Clocked out successfully.',
        );
    }

    /**
     * Get a paginated list of leave requests.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function leaveRequests(Request $request): JsonResponse
    {
        Gate::authorize('hr.view');

        $filters = $request->validate([
            'staff_id' => ['nullable', 'integer'],
            'status'   => ['nullable', new Enum(LeaveRequestStatus::class)],
        ]);

        $requests = $this->leaveService->paginateRequests(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($requests, LeaveRequestResource::collection($requests), 'Leave requests retrieved successfully.');
    }

    /**
     * Submit a leave request.
     *
     * @param StoreLeaveRequestRequest $request
     * @param Staff $staff
     * @return JsonResponse
     * @throws Throwable
     */
    public function submitLeave(StoreLeaveRequestRequest $request, Staff $staff): JsonResponse
    {
        Gate::authorize('hr.manage');

        $leaveRequest = $this->leaveService->submitRequest($staff, $request->validated());

        return $this->created(
            new LeaveRequestResource($leaveRequest->load(['staff', 'leaveType'])),
            'Leave request submitted successfully.',
        );
    }

    /**
     * Approve a leave request.
     *
     * @param  Request  $request
     * @param  LeaveRequest  $leaveRequest
     * @return JsonResponse
     */
    public function approveLeave(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        Gate::authorize('hr.manage');

        /** @var TenantUser $reviewer */
        $reviewer = $request->user();

        $leaveRequest = $this->leaveService->approve(
            $leaveRequest,
            $reviewer,
            $request->string('notes')->toString() ?: null,
        );

        return $this->success(
            new LeaveRequestResource($leaveRequest),
            'Leave request approved successfully.',
        );
    }

    /**
     * Reject a leave request.
     *
     * @param  Request  $request
     * @param  LeaveRequest  $leaveRequest
     * @return JsonResponse
     */
    public function rejectLeave(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        Gate::authorize('hr.manage');

        /** @var TenantUser $reviewer */
        $reviewer = $request->user();

        $leaveRequest = $this->leaveService->reject(
            $leaveRequest,
            $reviewer,
            $request->string('notes')->toString() ?: null,
        );

        return $this->success(
            new LeaveRequestResource($leaveRequest),
            'Leave request rejected successfully.',
        );
    }

    /**
     * Store a new leave type.
     *
     * @param  StoreLeaveTypeRequest  $request
     * @return JsonResponse
     */
    public function storeLeaveType(StoreLeaveTypeRequest $request): JsonResponse
    {
        Gate::authorize('hr.manage');

        $leaveType = $this->leaveService->createType($request->validated());

        return $this->created($leaveType, 'Leave type created successfully.');
    }

    /**
     * Upsert a payroll profile.
     *
     * @param UpsertPayrollProfileRequest $request
     * @param Staff $staff
     * @return JsonResponse
     * @throws Throwable
     */
    public function upsertPayroll(UpsertPayrollProfileRequest $request, Staff $staff): JsonResponse
    {
        Gate::authorize('hr.manage');

        $profile = $this->payrollProfileService->upsert($staff, $request->validated());

        return $this->success($profile, 'Payroll profile saved successfully.');
    }
}
