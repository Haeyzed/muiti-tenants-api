<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Manages job positions within departments.
 */
class PositionService
{
    /**
     * Paginate the positions.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Position>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Position::query()
            ->with('department')
            ->latest();

        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $query->where('title', 'like', "%{$search}%");
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a position by ID.
     *
     * @param int $id
     * @return Position
     */
    public function find(int $id): Position
    {
        return Position::query()
            ->with(['department', 'staff'])
            ->findOrFail($id);
    }

    /**
     * Create a new position.
     *
     * @param array<string, mixed> $data
     * @return Position
     */
    public function create(array $data): Position
    {
        return Position::query()->create($data);
    }

    /**
     * Update an existing position.
     *
     * @param Position $position
     * @param array<string, mixed> $data
     * @return Position
     */
    public function update(Position $position, array $data): Position
    {
        $position->update($data);

        return $position->fresh(['department']);
    }

    /**
     * Delete a position.
     *
     * @param Position $position
     * @return void
     */
    public function delete(Position $position): void
    {
        $position->delete();
    }

    /**
     * Delete multiple positions by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Position::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a position permanently.
     *
     * @param Position $position
     * @return void
     */
    public function forceDelete(Position $position): void
    {
        $position->forceDelete();
    }

    /**
     * Restore a soft-deleted customer.
     *
     * @param Position $position
     * @return Position
     */
    public function restore(Position $position): Position
    {
        $position->restore();

        return $position->fresh();
    }

    /**
     * Restore multiple soft-deleted positions by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Position::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
