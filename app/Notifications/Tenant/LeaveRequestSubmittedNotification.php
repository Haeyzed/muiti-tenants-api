<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\LeaveRequest;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts HR managers that a leave request was submitted.
 */
class LeaveRequestSubmittedNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::LeaveRequestSubmitted;
    }

    public function isAdminAlert(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $staff = $this->leaveRequest->staff;

        return (new MailMessage)
            ->subject('New leave request from '.$staff?->fullName())
            ->line('A leave request requires your review.')
            ->line('Dates: '.$this->leaveRequest->start_date->toDateString().' to '.$this->leaveRequest->end_date->toDateString())
            ->action('Review leave requests', url('/hr/leave-requests'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationEvent::LeaveRequestSubmitted->value,
            'leave_request_id' => $this->leaveRequest->id,
            'staff_id' => $this->leaveRequest->staff_id,
            'start_date' => $this->leaveRequest->start_date->toDateString(),
            'end_date' => $this->leaveRequest->end_date->toDateString(),
        ];
    }
}
