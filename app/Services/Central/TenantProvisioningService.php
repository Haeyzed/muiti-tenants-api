<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Enums\Central\TenantStatus;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Provisions new tenants including database and primary domain.
 */
class TenantProvisioningService
{
    public function __construct(
        private readonly DomainService $domainService,
    ) {}

    /**
     * Provision a new tenant.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function provision(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            /** @var Tenant $tenant */
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? TenantStatus::Pending->value,
                'plan' => $data['plan'] ?? null,
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'owner' => $data['owner'] ?? null,
            ]);

            $this->domainService->createSubdomain($tenant, (string) $data['subdomain'], isPrimary: true);

            return $tenant->load(['domains', 'primaryDomain']);
        });
    }
}
