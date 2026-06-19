<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

/**
 * Identifies tenant notification types for channel preferences.
 */
enum NotificationEvent: string
{
    case OrderPlaced = 'order_placed';
    case OrderStatusUpdated = 'order_status_updated';
    case PaymentInitiated = 'payment_initiated';
    case PaymentCompleted = 'payment_completed';
    case CheckoutSessionAdmitted = 'checkout_session_admitted';
    case WaitlistJoined = 'waitlist_joined';
    case TeamInvitationSent = 'team_invitation_sent';
    case LeaveRequestSubmitted = 'leave_request_submitted';
    case FlashSaleActivated = 'flash_sale_activated';
    case FlashSaleEnded = 'flash_sale_ended';
}
