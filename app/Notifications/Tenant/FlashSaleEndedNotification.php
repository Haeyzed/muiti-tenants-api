<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\FlashSale;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts store managers that a flash sale has ended.
 */
class FlashSaleEndedNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public FlashSale $flashSale) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::FlashSaleEnded;
    }

    public function isAdminAlert(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Flash sale ended: '.$this->flashSale->name)
            ->line('The flash sale has ended and is no longer accepting orders.')
            ->action('View flash sale', url('/flash-sales/'.$this->flashSale->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationEvent::FlashSaleEnded->value,
            'flash_sale_id' => $this->flashSale->id,
            'name' => $this->flashSale->name,
        ];
    }
}
