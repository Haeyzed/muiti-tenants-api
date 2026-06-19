<?php

declare(strict_types=1);

namespace App\Jobs\Central;

use App\Models\Central\Tenant;
use App\Services\Central\TenantOwnerProvisioningService;

/**
 * Creates the store-owner account after the tenant database is seeded.
 */
class CreateTenantOwner
{
    public function __construct(
        private readonly Tenant $tenant,
    ) {}

    public function handle(TenantOwnerProvisioningService $ownerProvisioningService): void
    {
        $tenant = Tenant::query()->findOrFail($this->tenant->getKey());

        $ownerProvisioningService->provision($tenant);
    }
}
