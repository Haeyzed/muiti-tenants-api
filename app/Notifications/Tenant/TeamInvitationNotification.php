<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\TeamInvitation;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends a team invitation email to a prospective staff member.
 */
class TeamInvitationNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public TeamInvitation $invitation) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::TeamInvitationSent;
    }

    public function isAdminAlert(): bool
    {
        return false;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inviter = $this->invitation->inviter?->name ?? 'A team member';

        return (new MailMessage)
            ->subject('You have been invited to join the team')
            ->line("{$inviter} invited you to join the store team as {$this->invitation->role}.")
            ->action('Accept invitation', url('/team/invitations/accept?token='.$this->invitation->token))
            ->line('This invitation expires on '.$this->invitation->expires_at->toDayDateTimeString().'.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationEvent::TeamInvitationSent->value,
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
            'role' => $this->invitation->role,
        ];
    }
}
