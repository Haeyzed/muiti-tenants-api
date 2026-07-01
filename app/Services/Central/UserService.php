<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Models\Central\CentralUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Manages central platform administrator accounts.
 */
class UserService
{
    /**
     * Paginate central users.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, CentralUser>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CentralUser::query()->latest();

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['is_active'])) {
            $statuses = (array) $filters['is_active'];
            $activeSelected = in_array('active', $statuses, true);
            $inactiveSelected = in_array('inactive', $statuses, true);

            if ($activeSelected && ! $inactiveSelected) {
                $query->where('is_active', true);
            } elseif ($inactiveSelected && ! $activeSelected) {
                $query->where('is_active', false);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a user by ID.
     */
    public function find(int $id): CentralUser
    {
        return CentralUser::query()->findOrFail($id);
    }

    /**
     * Create a new central user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CentralUser
    {
        return CentralUser::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make((string) $data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update an existing central user.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CentralUser $user, array $data): CentralUser
    {
        $payload = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make((string) $data['password']);
        }

        $user->update($payload);

        return $user->fresh();
    }

    /**
     * Delete a central user.
     */
    public function delete(CentralUser $user): void
    {
        $user->delete();
    }

    /**
     * Delete multiple users, excluding the current user.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids, int $currentUserId): int
    {
        return CentralUser::query()
            ->whereIn('id', $ids)
            ->where('id', '!=', $currentUserId)
            ->delete();
    }

    /**
     * Build the export query for users.
     *
     * @param  list<int>|null  $ids
     * @return Collection<int, CentralUser>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = CentralUser::query()->orderBy('name');

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get user statistics.
     *
     * @return array{total: int, active: int, inactive: int}
     */
    public function statistics(): array
    {
        return [
            'total' => CentralUser::query()->count(),
            'active' => CentralUser::query()->where('is_active', true)->count(),
            'inactive' => CentralUser::query()->where('is_active', false)->count(),
        ];
    }

    /**
     * Get user options for dropdowns.
     *
     * @return Collection<int, array{label: string, value: int, email: string}>
     */
    public function getOptions(): Collection
    {
        return CentralUser::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (CentralUser $user) => [
                'label' => $user->name,
                'value' => $user->id,
                'email' => $user->email,
            ]);
    }
}
