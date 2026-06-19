<?php

declare(strict_types=1);

namespace App\Events\Central;

use App\Models\Central\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a new tenant is provisioned on the platform.
 */
class TenantProvisioned
{
    use Dispatchable, SerializesModels;

    public function __construct(public Tenant $tenant) {}
}
