<?php

declare(strict_types=1);

namespace App\Jobs\Central;

use App\Events\Central\TenantProvisioned;
use App\Models\Central\Tenant;

/**
 * Marks tenant provisioning as complete after database setup finishes.
 */
class FinalizeTenantProvisioning
{
    public function __construct(
        private readonly Tenant $tenant,
    ) {}

    public function handle(): void
    {
        TenantProvisioned::dispatch($this->tenant->fresh(['domains', 'primaryDomain']));
    }
}
