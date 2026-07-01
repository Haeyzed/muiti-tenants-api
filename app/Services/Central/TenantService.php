<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Enums\Central\TenantStatus;
use App\Events\Central\TenantActivated;
use App\Events\Central\TenantSuspended;
use App\Models\Central\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Handles tenant lifecycle operations on the central platform.
 */
class TenantService
{
    public function __construct(
        private readonly TenantProvisioningService $provisioningService,
    )
    {
    }

    /**
     * Paginate tenants.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Tenant>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Tenant::query()
            ->with(['domains', 'primaryDomain'])
            ->latest();

        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a tenant by ID.
     *
     * @param string $id
     * @return Tenant
     */
    public function find(string $id): Tenant
    {
        return Tenant::query()
            ->with(['domains', 'settings', 'primaryDomain'])
            ->findOrFail($id);
    }

    /**
     * Create a new tenant.
     *
     * @param array<string, mixed> $data
     * @return Tenant
     * @throws Throwable
     */
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $slug = Str::slug((string)($data['slug'] ?? $data['name']));

            return $this->provisioningService->provision([
                'name' => $data['name'],
                'slug' => $slug,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => TenantStatus::Pending->value,
                'plan' => $data['plan'] ?? null,
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'subdomain' => $data['subdomain'] ?? $slug,
                'owner' => [
                    'name' => $data['owner']['name'],
                    'email' => $data['owner']['email'],
                    'phone' => $data['owner']['phone'] ?? null,
                ],
            ]);
        });
    }

    /**
     * Update an existing tenant.
     *
     * @param Tenant $tenant
     * @param array<string, mixed> $data
     * @return Tenant
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update([
            'name' => $data['name'] ?? $tenant->name,
            'email' => $data['email'] ?? $tenant->email,
            'phone' => $data['phone'] ?? $tenant->phone,
            'plan' => $data['plan'] ?? $tenant->plan,
            'trial_ends_at' => $data['trial_ends_at'] ?? $tenant->trial_ends_at,
        ]);

        return $tenant->fresh(['domains', 'settings', 'primaryDomain']);
    }

    /**
     * Activate a tenant.
     *
     * @param Tenant $tenant
     * @return Tenant
     */
    public function activate(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => TenantStatus::Active,
            'suspended_at' => null,
        ]);

        TenantActivated::dispatch($tenant);

        return $tenant->fresh();
    }

    /**
     * Suspend a tenant.
     *
     * @param Tenant $tenant
     * @return Tenant
     */
    public function suspend(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
        ]);

        TenantSuspended::dispatch($tenant);

        return $tenant->fresh();
    }

    /**
     * Delete a tenant.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }

    /**
     * @param list<string> $ids
     */
    public function deleteMany(array $ids): int
    {
        return Tenant::query()->whereIn('id', $ids)->delete();
    }

    /**
     * @param list<string>|null $ids
     * @return \Illuminate\Support\Collection<int, Tenant>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): \Illuminate\Support\Collection {
        $query = Tenant::query()->with(['domains'])->orderBy('name');

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
     * Get statistics about tenants.
     *
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => Tenant::query()->count(),
            'active' => Tenant::query()->where('status', TenantStatus::Active)->count(),
            'suspended' => Tenant::query()->where('status', TenantStatus::Suspended)->count(),
            'pending' => Tenant::query()->where('status', TenantStatus::Pending)->count(),
        ];
    }
}
