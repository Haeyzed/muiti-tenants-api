<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when tenant store settings are updated.
 */
class SettingsUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public string $section,
        public array $settings,
    ) {}
}
