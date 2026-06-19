<?php

declare(strict_types=1);

namespace App\Listeners\Tenant;

use App\Events\Tenant\FlashSaleActivated;
use App\Events\Tenant\FlashSaleEnded;
use App\Events\Tenant\LeaveRequestSubmitted;
use App\Events\Tenant\TeamInvitationSent;
use App\Notifications\Tenant\FlashSaleActivatedNotification;
use App\Notifications\Tenant\FlashSaleEndedNotification;
use App\Notifications\Tenant\LeaveRequestSubmittedNotification;
use App\Notifications\Tenant\TeamInvitationNotification;
use App\Services\Tenant\NotificationDispatchService;

/**
 * Sends staff and operational notifications for tenant events.
 */
class HandleTenantStaffNotifications
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {}

    public function handleTeamInvitationSent(TeamInvitationSent $event): void
    {
        $invitation = $event->invitation->loadMissing('inviter');

        $this->notificationDispatchService->notifyMail(
            $invitation->email,
            new TeamInvitationNotification($invitation),
        );
    }

    public function handleLeaveRequestSubmitted(LeaveRequestSubmitted $event): void
    {
        $leaveRequest = $event->leaveRequest->loadMissing(['staff', 'leaveType']);

        $managers = $this->notificationDispatchService->staffWithPermission('hr.manage');

        $this->notificationDispatchService->notifyUsers(
            $managers,
            new LeaveRequestSubmittedNotification($leaveRequest),
        );
    }

    public function handleFlashSaleActivated(FlashSaleActivated $event): void
    {
        $flashSale = $event->flashSale;

        $managers = $this->notificationDispatchService->staffWithPermission('flash-sales.manage');

        $this->notificationDispatchService->notifyUsers(
            $managers,
            new FlashSaleActivatedNotification($flashSale),
        );
    }

    public function handleFlashSaleEnded(FlashSaleEnded $event): void
    {
        $flashSale = $event->flashSale;

        $managers = $this->notificationDispatchService->staffWithPermission('flash-sales.manage');

        $this->notificationDispatchService->notifyUsers(
            $managers,
            new FlashSaleEndedNotification($flashSale),
        );
    }
}
