<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreCustomerGroupRequest;
use App\Http\Requests\Tenant\UpdateCustomerGroupRequest;
use App\Http\Resources\Tenant\CustomerGroupResource;
use App\Models\Tenant\CustomerGroup;
use App\Services\Tenant\CustomerGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages customer groups.
 */
class CustomerGroupController extends ApiController
{
    public function __construct(
        private readonly CustomerGroupService $customerGroupService,
    ) {}

    /**
     * Get a paginated list of customer groups.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CustomerGroup::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:active,inactive'],
        ]);

        $groups = $this->customerGroupService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($groups, CustomerGroupResource::collection($groups), 'Customer groups retrieved successfully.');
    }

    /**
     * Create a new customer group.
     *
     * @param  StoreCustomerGroupRequest  $request
     * @return JsonResponse
     */
    public function store(StoreCustomerGroupRequest $request): JsonResponse
    {
        $this->authorize('create', CustomerGroup::class);

        $group = $this->customerGroupService->create($request->validated());

        return $this->created(
            new CustomerGroupResource($group),
            'Customer group created successfully.',
        );
    }

    /**
     * Get a single customer group.
     *
     * @param  CustomerGroup  $customerGroup
     * @return JsonResponse
     */
    public function show(CustomerGroup $customerGroup): JsonResponse
    {
        $this->authorize('view', $customerGroup);

        return $this->success(
            new CustomerGroupResource($this->customerGroupService->find($customerGroup->id)),
            'Customer group retrieved successfully.',
        );
    }

    /**
     * Update an existing customer group.
     *
     * @param  UpdateCustomerGroupRequest  $request
     * @param  CustomerGroup  $customerGroup
     * @return JsonResponse
     */
    public function update(UpdateCustomerGroupRequest $request, CustomerGroup $customerGroup): JsonResponse
    {
        $this->authorize('update', $customerGroup);

        $group = $this->customerGroupService->update($customerGroup, $request->validated());

        return $this->updated(
            new CustomerGroupResource($group),
            'Customer group updated successfully.',
        );
    }

    /**
     * Delete a customer group.
     *
     * @param  CustomerGroup  $customerGroup
     * @return JsonResponse
     */
    public function destroy(CustomerGroup $customerGroup): JsonResponse
    {
        $this->authorize('delete', $customerGroup);

        $this->customerGroupService->delete($customerGroup);

        return $this->deleted('Customer group deleted successfully.');
    }

    /**
     * Delete multiple customer groups.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', CustomerGroup::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:customer_groups,id'],
        ]);

        $count = $this->customerGroupService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} customer groups deleted successfully.");
    }

    /**
     * Force delete a customer group permanently.
     *
     * @param  CustomerGroup  $customerGroup
     * @return JsonResponse
     */
    public function forceDestroy(CustomerGroup $customerGroup): JsonResponse
    {
        $this->authorize('forceDelete', $customerGroup);

        $this->customerGroupService->forceDelete($customerGroup);

        return $this->deleted('Customer group permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted customer group.
     *
     * @param  CustomerGroup  $customerGroup
     * @return JsonResponse
     */
    public function restore(CustomerGroup $customerGroup): JsonResponse
    {
        $this->authorize('restore', $customerGroup);

        $customerGroup = $this->customerGroupService->restore($customerGroup);

        return $this->success(
            new CustomerGroupResource($customerGroup),
            'Customer group restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted customer groups.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', CustomerGroup::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->customerGroupService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} customer groups restored successfully.");
    }
}
