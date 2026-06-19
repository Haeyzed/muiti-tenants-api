<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\CustomerCreated;
use App\Models\Tenant\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages customers within a tenant store.
 */
class CustomerService
{
    /**
     * Paginate the customers.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->with(['group', 'tags'])
            ->filter($filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a customer by ID.
     *
     * @param int $id
     * @return Customer
     */
    public function find(int $id): Customer
    {
        return Customer::query()
            ->with(['user', 'group', 'tags', 'addresses', 'notes.author'])
            ->findOrFail($id);
    }

    /**
     * Create a new customer.
     *
     * @param array<string, mixed> $data
     * @return Customer
     * @throws Throwable
     */
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            $tagIds = $data['tag_ids'] ?? [];
            unset($data['tag_ids']);

            $customer = Customer::query()->create($data);

            if ($tagIds !== []) {
                $customer->tags()->sync($tagIds);
            }

            $customer = $this->find($customer->id);
            CustomerCreated::dispatch($customer);

            return $customer;
        });
    }

    /**
     * Update an existing customer.
     *
     * @param Customer $customer
     * @param array<string, mixed> $data
     * @return Customer
     * @throws Throwable
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);

            $customer->update($data);

            if ($tagIds !== null) {
                $customer->tags()->sync($tagIds);
            }

            return $this->find($customer->id);
        });
    }

    /**
     * Delete a customer.
     *
     * @param Customer $customer
     * @return void
     */
    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    /**
     * Delete multiple customers by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Customer::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a customer permanently.
     *
     * @param Customer $customer
     * @return void
     */
    public function forceDelete(Customer $customer): void
    {
        $customer->forceDelete();
    }

    /**
     * Restore a soft-deleted customer.
     *
     * @param Customer $customer
     * @return Customer
     */
    public function restore(Customer $customer): Customer
    {
        $customer->restore();

        return $customer->fresh();
    }

    /**
     * Restore multiple soft-deleted customers by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Customer::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
