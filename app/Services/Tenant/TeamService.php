<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\LoginHistory;
use App\Models\Tenant\TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages tenant team members.
 */
class TeamService
{
    /**
     * Paginate team members.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, TenantUser>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TenantUser::query()
            ->with('roles')
            ->latest();

        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a team member by ID.
     *
     * @param int $id
     * @return TenantUser
     */
    public function find(int $id): TenantUser
    {
        return TenantUser::query()
            ->with(['roles', 'permissions', 'loginHistories'])
            ->findOrFail($id);
    }

    /**
     * Create a new team member.
     *
     * @param array<string, mixed> $data
     * @return TenantUser
     * @throws Throwable
     */
    public function create(array $data): TenantUser
    {
        return DB::transaction(function () use ($data): TenantUser {
            $role = $data['role'] ?? null;
            unset($data['role']);

            $user = TenantUser::query()->create($data);

            if ($role !== null) {
                $user->assignRole($role);
            }

            return $user->load('roles');
        });
    }

    /**
     * Update a team member.
     *
     * @param TenantUser $user
     * @param array<string, mixed> $data
     * @return TenantUser
     * @throws Throwable
     */
    public function update(TenantUser $user, array $data): TenantUser
    {
        return DB::transaction(function () use ($user, $data): TenantUser {
            $role = $data['role'] ?? null;
            unset($data['role']);

            $user->update($data);

            if ($role !== null) {
                $user->syncRoles([$role]);
            }

            return $user->fresh(['roles']);
        });
    }

    /**
     * Suspend a team member.
     *
     * @param TenantUser $user
     * @return TenantUser
     */
    public function suspend(TenantUser $user): TenantUser
    {
        $user->update(['suspended_at' => now()]);

        return $user->fresh();
    }

    /**
     * Unsuspend a team member.
     *
     * @param TenantUser $user
     * @return TenantUser
     */
    public function unsuspend(TenantUser $user): TenantUser
    {
        $user->update(['suspended_at' => null]);

        return $user->fresh();
    }

    /**
     * Record a login event for a team member.
     *
     * @param TenantUser $user
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return LoginHistory
     */
    public function recordLogin(TenantUser $user, ?string $ipAddress = null, ?string $userAgent = null): LoginHistory
    {
        return $user->loginHistories()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'logged_in_at' => now(),
        ]);
    }

    /**
     * Record a logout event for a team member.
     *
     * @param LoginHistory $loginHistory
     * @return LoginHistory
     */
    public function recordLogout(LoginHistory $loginHistory): LoginHistory
    {
        $loginHistory->update(['logged_out_at' => now()]);

        return $loginHistory->fresh();
    }

    /**
     * Delete a team member.
     *
     * @param TenantUser $user
     * @return void
     */
    public function delete(TenantUser $user): void
    {
        $user->delete();
    }
}
