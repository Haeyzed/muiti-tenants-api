<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\Order;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a customer of an order status change.
 */
class OrderStatusUpdatedNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public Order $order) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::OrderStatusUpdated;
    }

    public function isAdminAlert(): bool
    {
        return false;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order '.$this->order->order_number.' status updated')
            ->line('Your order status is now: '.$this->order->status?->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationEvent::OrderStatusUpdated->value,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status?->value,
        ];
    }
}
