<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Payment;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for payment operations.
 */
class PaymentPolicy
{
    public function view(TenantUser $user, Payment $payment): bool
    {
        return $user->can('payments.view')
            || ($user->can('payments.initiate') && $payment->order->customer_id === $user->customer?->id);
    }

    public function initiate(TenantUser $user): bool
    {
        return $user->can('payments.initiate') && $user->customer !== null;
    }

    public function refund(TenantUser $user, Payment $payment): bool
    {
        return $user->can('payments.manage');
    }
}
