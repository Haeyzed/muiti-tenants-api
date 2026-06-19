<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\CheckoutSession;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a customer that their checkout session has been admitted.
 */
class CheckoutSessionAdmittedNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public CheckoutSession $session) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::CheckoutSessionAdmitted;
    }

    public function isAdminAlert(): bool
    {
        return false;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your checkout spot is ready')
            ->line('You have been admitted to the checkout queue.')
            ->line('Complete your purchase before your session expires.')
            ->action('Continue checkout', url('/checkout?token='.$this->session->session_token));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationEvent::CheckoutSessionAdmitted->value,
            'session_token' => $this->session->session_token,
            'expires_at' => $this->session->expires_at?->toIso8601String(),
        ];
    }
}
