<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a payment is successfully completed.
 */
class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payment $payment) {}
}
