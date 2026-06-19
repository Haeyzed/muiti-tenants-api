<?php

declare(strict_types=1);

namespace App\Listeners\Tenant;

use App\Events\Tenant\CheckoutSessionAdmitted;
use App\Events\Tenant\OrderPlaced;
use App\Events\Tenant\OrderStatusUpdated;
use App\Events\Tenant\PaymentCompleted;
use App\Events\Tenant\PaymentInitiated;
use App\Events\Tenant\WaitlistJoined;
use App\Notifications\Tenant\CheckoutSessionAdmittedNotification;
use App\Notifications\Tenant\OrderPlacedNotification;
use App\Notifications\Tenant\OrderStatusUpdatedNotification;
use App\Notifications\Tenant\PaymentCompletedNotification;
use App\Notifications\Tenant\PaymentInitiatedNotification;
use App\Notifications\Tenant\WaitlistJoinedNotification;
use App\Services\Tenant\AnalyticsService;
use App\Services\Tenant\NotificationDispatchService;

/**
 * Sends customer notifications and records analytics for domain events.
 */
class HandleTenantDomainEvents
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing(['customer.user', 'items']);

        $this->notificationDispatchService->notifyUser(
            $order->customer?->user,
            new OrderPlacedNotification($order),
        );

        $this->analyticsService->recordOrderPlaced($order);
    }

    public function handleOrderStatusUpdated(OrderStatusUpdated $event): void
    {
        $order = $event->order->loadMissing('customer.user');

        $this->notificationDispatchService->notifyUser(
            $order->customer?->user,
            new OrderStatusUpdatedNotification($order),
        );
    }

    public function handlePaymentInitiated(PaymentInitiated $event): void
    {
        $payment = $event->payment->loadMissing(['order.customer.user']);

        $this->notificationDispatchService->notifyUser(
            $payment->order->customer?->user,
            new PaymentInitiatedNotification($payment),
        );
    }

    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $payment = $event->payment->loadMissing(['order.customer.user']);

        $this->notificationDispatchService->notifyUser(
            $payment->order->customer?->user,
            new PaymentCompletedNotification($payment),
        );

        $this->analyticsService->recordPaymentCompleted($payment);
    }

    public function handleCheckoutSessionAdmitted(CheckoutSessionAdmitted $event): void
    {
        $session = $event->session->loadMissing('customer.user');

        $this->notificationDispatchService->notifyUser(
            $session->customer?->user,
            new CheckoutSessionAdmittedNotification($session),
        );
    }

    public function handleWaitlistJoined(WaitlistJoined $event): void
    {
        $subscriber = $event->subscriber->loadMissing('customer.user');

        $this->notificationDispatchService->notifyUser(
            $subscriber->customer?->user,
            new WaitlistJoinedNotification($subscriber),
        );
    }
}
