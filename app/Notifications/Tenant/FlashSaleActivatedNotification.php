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
 * Alerts store managers that a flash sale has gone live.
 */
class FlashSaleActivatedNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public FlashSale $flashSale) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::FlashSaleActivated;
    }

    public function isAdminAlert(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Flash sale live: '.$this->flashSale->name)
            ->line('The flash sale is now active.')
            ->line('Ends at: '.$this->flashSale->ends_at?->toDayDateTimeString())
            ->action('View flash sale', url('/flash-sales/'.$this->flashSale->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationEvent::FlashSaleActivated->value,
            'flash_sale_id' => $this->flashSale->id,
            'name' => $this->flashSale->name,
            'ends_at' => $this->flashSale->ends_at?->toIso8601String(),
        ];
    }
}
