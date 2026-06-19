<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Manages in-app notifications for tenant users.
 */
class NotificationController extends ApiController
{
    /**
     * Get a paginated list of notifications.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated(
            $notifications,
            $notifications->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? class_basename($notification->type),
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]),
            'Notifications retrieved successfully.',
        );
    }

    /**
     * Mark a notification as read.
     *
     * @param  Request  $request
     * @param  string  $notificationId
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $notification = $request->user()->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return $this->success(null, 'Notification marked as read successfully.');
    }

    /**
     * Mark all notifications as read.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $request->user()->unreadNotifications->markAsRead();

        return $this->success(null, 'All notifications marked as read successfully.');
    }
}
