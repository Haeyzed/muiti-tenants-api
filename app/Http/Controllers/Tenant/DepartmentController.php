<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreDepartmentRequest;
use App\Http\Requests\Tenant\UpdateDepartmentRequest;
use App\Http\Resources\Tenant\DepartmentResource;
use App\Models\Tenant\Department;
use App\Services\Tenant\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages departments.
 */
class DepartmentController extends ApiController
{
    public function __construct(
        private readonly DepartmentService $departmentService,
    ) {}

    /**
     * Get a paginated list of departments.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:active,inactive'],
        ]);

        $departments = $this->departmentService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($departments, DepartmentResource::collection($departments), 'Departments retrieved successfully.');
    }

    /**
     * Create a new department.
     *
     * @param  StoreDepartmentRequest  $request
     * @return JsonResponse
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        $department = $this->departmentService->create($request->validated());

        return $this->created(
            new DepartmentResource($department),
            'Department created successfully.',
        );
    }

    /**
     * Get a single department.
     *
     * @param  Department  $department
     * @return JsonResponse
     */
    public function show(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        return $this->success(
            new DepartmentResource($this->departmentService->find($department->id)),
            'Department retrieved successfully.',
        );
    }

    /**
     * Update an existing department.
     *
     * @param  UpdateDepartmentRequest  $request
     * @param  Department  $department
     * @return JsonResponse
     */
    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        $department = $this->departmentService->update($department, $request->validated());

        return $this->updated(
            new DepartmentResource($department),
            'Department updated successfully.',
        );
    }

    /**
     * Delete a department.
     *
     * @param  Department  $department
     * @return JsonResponse
     */
    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        $this->departmentService->delete($department);

        return $this->deleted('Department deleted successfully.');
    }

    /**
     * Delete multiple departments.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Department::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:departments,id'],
        ]);

        $count = $this->departmentService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} departments deleted successfully.");
    }

    /**
     * Force delete a department permanently.
     *
     * @param  Department  $department
     * @return JsonResponse
     */
    public function forceDestroy(Department $department): JsonResponse
    {
        $this->authorize('forceDelete', $department);

        $this->departmentService->forceDelete($department);

        return $this->deleted('Department permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted department.
     *
     * @param  Department  $department
     * @return JsonResponse
     */
    public function restore(Department $department): JsonResponse
    {
        $this->authorize('restore', $department);

        $department = $this->departmentService->restore($department);

        return $this->success(
            new DepartmentResource($department),
            'Department restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted departments.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Department::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->departmentService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} departments restored successfully.");
    }
}
