<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\CustomerGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Manages customer groups.
 */
class CustomerGroupService
{
    /**
     * Paginate the customer groups.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, CustomerGroup>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return CustomerGroup::query()
            ->filter($filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a customer group by ID.
     *
     * @param int $id
     * @return CustomerGroup
     */
    public function find(int $id): CustomerGroup
    {
        return CustomerGroup::query()
            ->withCount('customers')
            ->findOrFail($id);
    }

    /**
     * Create a new customer group.
     *
     * @param array<string, mixed> $data
     * @return CustomerGroup
     */
    public function create(array $data): CustomerGroup
    {
        return CustomerGroup::query()->create($data);
    }

    /**
     * Update an existing customer group.
     *
     * @param CustomerGroup $group
     * @param array<string, mixed> $data
     * @return CustomerGroup
     */
    public function update(CustomerGroup $group, array $data): CustomerGroup
    {
        $group->update($data);

        return $group->fresh();
    }

    /**
     * Delete a customer group.
     *
     * @param CustomerGroup $group
     * @return void
     */
    public function delete(CustomerGroup $group): void
    {
        $group->delete();
    }

    /**
     * Delete multiple customer groups by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return CustomerGroup::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a customer group permanently.
     *
     * @param CustomerGroup $group
     * @return void
     */
    public function forceDelete(CustomerGroup $group): void
    {
        $group->forceDelete();
    }

    /**
     * Restore a soft-deleted customer group.
     *
     * @param CustomerGroup $group
     * @return CustomerGroup
     */
    public function restore(CustomerGroup $group): CustomerGroup
    {
        $group->restore();

        return $group->fresh();
    }

    /**
     * Restore multiple soft-deleted customer groups by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return CustomerGroup::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
