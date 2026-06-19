<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\CheckoutSessionStatus;
use App\Models\Tenant\CheckoutSession;

/**
 * Manages individual checkout session state within a queue.
 */
class CheckoutSessionService
{
    /**
     * Admit a checkout session from the queue.
     *
     * @param CheckoutSession $session
     * @param int $ttlSeconds
     * @return CheckoutSession
     */
    public function admit(CheckoutSession $session, int $ttlSeconds): CheckoutSession
    {
        $session->update([
            'status' => CheckoutSessionStatus::Admitted,
            'admitted_at' => now(),
            'expires_at' => now()->addSeconds($ttlSeconds),
            'queue_position' => null,
        ]);

        return $session->fresh();
    }

    /**
     * Expire a checkout session.
     *
     * @param CheckoutSession $session
     * @return CheckoutSession
     */
    public function expire(CheckoutSession $session): CheckoutSession
    {
        $session->update([
            'status' => CheckoutSessionStatus::Expired,
        ]);

        return $session->fresh();
    }

    /**
     * Complete a checkout session.
     *
     * @param CheckoutSession $session
     * @return CheckoutSession
     */
    public function complete(CheckoutSession $session): CheckoutSession
    {
        $session->update([
            'status' => CheckoutSessionStatus::Completed,
        ]);

        return $session->fresh();
    }
}
