<?php

declare(strict_types=1);

namespace App\Events\Central;

use App\Models\Central\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a suspended tenant is reactivated.
 */
class TenantActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Tenant $tenant) {}
}
