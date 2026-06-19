<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\Tenant\EmploymentStatus;
use App\Enums\Tenant\EmploymentType;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreStaffRequest;
use App\Http\Requests\Tenant\UpdateStaffRequest;
use App\Http\Resources\Tenant\StaffResource;
use App\Models\Tenant\Staff;
use App\Services\Tenant\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Throwable;

/**
 * Manages staff members.
 */
class StaffController extends ApiController
{
    public function __construct(
        private readonly StaffService $staffService,
    ) {}

    /**
     * Get a paginated list of staff members.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Staff::class);

        $filters = $request->validate([
            'search'            => ['nullable', 'string'],
            'department_id'     => ['nullable', 'integer'],
            'employment_status' => ['nullable', new Enum(EmploymentStatus::class)],
            'employment_type' => ['nullable', new Enum(EmploymentType::class)],
        ]);

        $staff = $this->staffService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($staff, StaffResource::collection($staff), 'Staff members retrieved successfully.');
    }

    /**
     * Create a new staff member.
     *
     * @param StoreStaffRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $this->authorize('create', Staff::class);

        $staff = $this->staffService->create($request->validated());

        return $this->created(
            new StaffResource($staff),
            'Staff member created successfully.',
        );
    }

    /**
     * Get a single staff member.
     *
     * @param  Staff  $staff
     * @return JsonResponse
     */
    public function show(Staff $staff): JsonResponse
    {
        $this->authorize('view', $staff);

        return $this->success(
            new StaffResource($this->staffService->find($staff->id)),
            'Staff member retrieved successfully.',
        );
    }

    /**
     * Update an existing staff member.
     *
     * @param UpdateStaffRequest $request
     * @param Staff $staff
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(UpdateStaffRequest $request, Staff $staff): JsonResponse
    {
        $this->authorize('update', $staff);

        $staff = $this->staffService->update($staff, $request->validated());

        return $this->updated(
            new StaffResource($staff),
            'Staff member updated successfully.',
        );
    }

    /**
     * Delete a staff member.
     *
     * @param  Staff  $staff
     * @return JsonResponse
     */
    public function destroy(Staff $staff): JsonResponse
    {
        $this->authorize('delete', $staff);

        $this->staffService->delete($staff);

        return $this->deleted('Staff member deleted successfully.');
    }
}
