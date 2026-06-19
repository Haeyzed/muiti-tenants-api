<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\TeamInvitation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a team invitation is sent.
 */
class TeamInvitationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public TeamInvitation $invitation) {}
}
