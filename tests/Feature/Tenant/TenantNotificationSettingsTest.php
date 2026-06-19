<?php

declare(strict_types=1);

use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\NotificationSetting;
use App\Models\Tenant\TeamInvitation;
use App\Notifications\Tenant\TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;

it('does not send customer notifications when event channels are disabled', function (): void {
    Notification::fake();

    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);

    $settings = NotificationSetting::singleton();
    $settings->update([
        'email_enabled' => false,
        'push_enabled' => true,
    ]);

    expect($settings->fresh()->channelsFor(NotificationEvent::OrderPlaced))
        ->toBe(['database']);

    $settings->update([
        'channels' => [
            NotificationEvent::OrderPlaced->value => false,
        ],
    ]);

    expect($settings->fresh()->channelsFor(NotificationEvent::OrderPlaced))
        ->toBe([]);

    $product = \App\Models\Tenant\Product::factory()->create(['price' => 15.00]);
    \App\Models\Tenant\Inventory::query()->create([
        'product_id' => $product->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 3,
    ]);

    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/cart/items", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders")
        ->assertCreated();

    Notification::assertNothingSent();
});

it('sends team invitation email to invitee', function (): void {
    Notification::fake();

    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/team/invitations", [
            'email' => 'new.manager@example.com',
            'role' => 'store-manager',
        ])
        ->assertCreated();

    Notification::assertSentOnDemand(
        TeamInvitationNotification::class,
        function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'new.manager@example.com'
                && $notification->invitation->email === 'new.manager@example.com';
        },
    );
});

it('notifies hr managers when leave is submitted', function (): void {
    Notification::fake();

    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);

    $staff = \App\Models\Tenant\Staff::factory()->create();

    $leaveType = \App\Models\Tenant\LeaveType::query()->create([
        'name' => 'Annual Leave',
        'code' => 'annual',
        'default_days' => 20,
        'is_paid' => true,
        'is_active' => true,
    ]);

    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/hr/staff/{$staff->id}/leave-requests", [
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'reason' => 'Annual leave',
        ])
        ->assertCreated();

    Notification::assertSentTo($ctx->user, \App\Notifications\Tenant\LeaveRequestSubmittedNotification::class);
});
