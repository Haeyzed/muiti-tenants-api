<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\WaitlistSubscriber;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms a customer joined a product waitlist.
 */
class WaitlistJoinedNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public WaitlistSubscriber $subscriber) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::WaitlistJoined;
    }

    public function isAdminAlert(): bool
    {
        return false;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You joined the waitlist')
            ->line('We will notify you when the product becomes available.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationEvent::WaitlistJoined->value,
            'subscriber_id' => $this->subscriber->id,
            'email' => $this->subscriber->email,
        ];
    }
}
