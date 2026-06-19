<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Enums\Central\BillingProvider;
use App\Http\Controllers\ApiController;
use App\Services\Central\PlatformSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles billing webhooks for gateway providers.
 */
class BillingWebhookController extends ApiController
{
    public function __construct(
        private readonly PlatformSubscriptionService $platformSubscriptionService,
    ) {}

    public function handle(Request $request, string $provider): JsonResponse
    {
        $billingProvider = BillingProvider::tryFrom($provider);

        if ($billingProvider === null || ! $billingProvider->isGateway()) {
            return $this->notFound('Unsupported billing provider.');
        }

        $subscription = $this->platformSubscriptionService->activateFromWebhook(
            $billingProvider,
            $request->all(),
        );

        if ($subscription === null) {
            return $this->notFound('Subscription not found for webhook payload.');
        }

        return $this->success([
            'tenant_id' => $subscription->tenant_id,
            'status' => $subscription->status->value,
            'plan' => $subscription->plan_slug,
        ], 'Subscription activated successfully.');
    }
}
