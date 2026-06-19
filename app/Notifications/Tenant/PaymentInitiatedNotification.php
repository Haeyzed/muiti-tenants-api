<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\Payment;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a customer that payment has been initiated for their order.
 */
class PaymentInitiatedNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public Payment $payment) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::PaymentInitiated;
    }

    public function isAdminAlert(): bool
    {
        return false;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Complete payment for order '.$this->payment->order->order_number)
            ->line('A payment of '.$this->payment->currency.' '.$this->payment->amount.' is pending.')
            ->line('Reference: '.$this->payment->provider_reference);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationEvent::PaymentInitiated->value,
            'payment_id' => $this->payment->id,
            'order_id' => $this->payment->order_id,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'provider_reference' => $this->payment->provider_reference,
        ];
    }
}
