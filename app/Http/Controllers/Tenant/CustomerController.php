<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreCustomerRequest;
use App\Http\Requests\Tenant\UpdateCustomerRequest;
use App\Http\Resources\Tenant\CustomerResource;
use App\Models\Tenant\Customer;
use App\Services\Tenant\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Manages customers within a tenant store.
 */
class CustomerController extends ApiController
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    /**
     * Get a paginated list of customers.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:active,inactive'],
            'customer_group_id' => ['nullable', 'integer'],
        ]);

        $customers = $this->customerService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($customers, CustomerResource::collection($customers), 'Customers retrieved successfully.');
    }

    /**
     * Create a new customer.
     *
     * @param StoreCustomerRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $this->customerService->create($request->validated());

        return $this->created(
            new CustomerResource($customer),
            'Customer created successfully.',
        );
    }

    /**
     * Get a single customer.
     *
     * @param  Customer  $customer
     * @return JsonResponse
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success(new CustomerResource($this->customerService->find($customer->id)), 'Customer retrieved successfully.');
    }

    /**
     * Update an existing customer.
     *
     * @param UpdateCustomerRequest $request
     * @param Customer $customer
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer = $this->customerService->update($customer, $request->validated());

        return $this->updated(
            new CustomerResource($customer),
            'Customer updated successfully.',
        );
    }

    /**
     * Delete a customer.
     *
     * @param  Customer  $customer
     * @return JsonResponse
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $this->customerService->delete($customer);

        return $this->deleted('Customer deleted successfully.');
    }

    /**
     * Delete multiple customers.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Customer::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:customers,id'],
        ]);

        $count = $this->customerService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} customers deleted successfully.");
    }

    /**
     * Force delete a customer permanently.
     *
     * @param  Customer  $customer
     * @return JsonResponse
     */
    public function forceDestroy(Customer $customer): JsonResponse
    {
        $this->authorize('forceDelete', $customer);

        $this->customerService->forceDelete($customer);

        return $this->deleted('Customer permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted customer.
     *
     * @param  Customer  $customer
     * @return JsonResponse
     */
    public function restore(Customer $customer): JsonResponse
    {
        $this->authorize('restore', $customer);

        $customer = $this->customerService->restore($customer);

        return $this->success(
            new CustomerResource($customer),
            'Customer restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted customers.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Customer::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->customerService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} customers restored successfully.");
    }
}
