<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\Staff;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a new staff member is created.
 */
class StaffCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Staff $staff) {}
}
