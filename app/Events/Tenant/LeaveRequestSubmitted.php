<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\LeaveRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a leave request is submitted.
 */
class LeaveRequestSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public LeaveRequest $leaveRequest) {}
}
