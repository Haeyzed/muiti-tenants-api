<?php

declare(strict_types=1);

namespace App\Jobs\Central;

use App\Models\Central\Tenant;

/**
 * Raises the PHP execution time limit before heavy tenant provisioning steps.
 */
class RaiseProvisioningTimeLimit
{
    public function __construct(
        private readonly Tenant $tenant,
    ) {}

    public function handle(): void
    {
        set_time_limit((int) config('tenancy.provision_timeout', 300));
    }
}
