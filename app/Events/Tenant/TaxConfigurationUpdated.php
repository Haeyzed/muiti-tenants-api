<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when tax configuration is updated.
 */
class TaxConfigurationUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $section) {}
}
