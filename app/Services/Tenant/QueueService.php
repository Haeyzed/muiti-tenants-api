<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\CheckoutSessionStatus;
use App\Events\Tenant\CheckoutSessionAdmitted;
use App\Models\Tenant\CheckoutQueue;
use App\Models\Tenant\CheckoutSession;
use App\Models\Tenant\Customer;
use App\Models\Tenant\FlashSale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Manages virtual waiting room queues for flash sale checkouts.
 */
class QueueService
{
    public function __construct(
        private readonly CheckoutSessionService $sessionService,
        private readonly AnalyticsService       $analyticsService,
    )
    {
    }

    /**
     * Ensure a checkout queue exists for a flash sale.
     *
     * @param FlashSale $flashSale
     * @return CheckoutQueue
     */
    public function ensureQueue(FlashSale $flashSale): CheckoutQueue
    {
        return $flashSale->checkoutQueue()->firstOrCreate(
            ['flash_sale_id' => $flashSale->id],
            [
                'name' => $flashSale->name . ' Queue',
                'max_concurrent_sessions' => 100,
                'session_ttl_seconds' => 600,
                'is_active' => true,
            ],
        );
    }

    /**
     * Join the checkout queue for a flash sale.
     *
     * @param FlashSale $flashSale
     * @param Customer|null $customer
     * @return CheckoutSession
     * @throws Throwable
     */
    public function join(FlashSale $flashSale, ?Customer $customer = null): CheckoutSession
    {
        return DB::transaction(function () use ($flashSale, $customer): CheckoutSession {
            $queue = $this->ensureQueue($flashSale);

            $position = $queue->sessions()
                    ->where('status', CheckoutSessionStatus::Waiting)
                    ->count() + 1;

            $session = $queue->sessions()->create([
                'customer_id' => $customer?->id,
                'session_token' => Str::random(64),
                'queue_position' => $position,
                'status' => CheckoutSessionStatus::Waiting,
            ]);

            $this->analyticsService->recordQueueEntry($flashSale->id);

            $this->processQueue($queue);

            return $session->fresh();
        });
    }

    /**
     * Process the checkout queue.
     *
     * @param CheckoutQueue $queue
     * @return void
     */
    public function processQueue(CheckoutQueue $queue): void
    {
        $queue->refresh();

        $availableSlots = $queue->max_concurrent_sessions - $queue->activeSessionCount();

        if ($availableSlots <= 0) {
            return;
        }

        $waitingSessions = $queue->sessions()
            ->where('status', CheckoutSessionStatus::Waiting)
            ->orderBy('queue_position')
            ->limit($availableSlots)
            ->get();

        foreach ($waitingSessions as $session) {
            $admitted = $this->sessionService->admit($session, $queue->session_ttl_seconds);
            CheckoutSessionAdmitted::dispatch($admitted);
        }
    }

    /**
     * Get the status of a checkout session.
     *
     * @param string $sessionToken
     * @return CheckoutSession|null
     */
    public function getSessionStatus(string $sessionToken): ?CheckoutSession
    {
        $session = CheckoutSession::query()
            ->where('session_token', $sessionToken)
            ->with('queue.flashSale')
            ->first();

        if ($session === null) {
            return null;
        }

        if ($session->expires_at !== null && $session->expires_at->isPast()) {
            $this->sessionService->expire($session);
        }

        return $session->fresh();
    }

    /**
     * Expire stale checkout sessions.
     *
     * @return int
     */
    public function expireStaleSessions(): int
    {
        $expired = CheckoutSession::query()
            ->where('status', CheckoutSessionStatus::Admitted)
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $session) {
            $this->sessionService->expire($session);
        }

        return $expired->count();
    }
}
