<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\CheckoutSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a user is admitted from the checkout queue.
 */
class CheckoutSessionAdmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public CheckoutSession $session) {}
}
