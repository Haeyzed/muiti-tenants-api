<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\WaitlistSubscriber;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a user joins a waitlist.
 */
class WaitlistJoined
{
    use Dispatchable, SerializesModels;

    public function __construct(public WaitlistSubscriber $subscriber) {}
}
