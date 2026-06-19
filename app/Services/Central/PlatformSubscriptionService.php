<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Enums\Central\BillingProvider;
use App\Enums\Central\PlatformSubscriptionStatus;
use App\Models\Central\PlatformSubscription;
use App\Models\Central\Tenant;

/**
 * Activates gateway subscriptions from provider webhook payloads.
 */
class PlatformSubscriptionService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function activateFromWebhook(BillingProvider $provider, array $payload): ?PlatformSubscription
    {
        $tenantId = data_get($payload, 'metadata.tenant_id')
            ?? data_get($payload, 'meta.tenant_id')
            ?? data_get($payload, 'custom_id');

        if ($tenantId === null) {
            return null;
        }

        $subscription = PlatformSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('provider', $provider)
            ->latest('id')
            ->first();

        if ($subscription === null) {
            return null;
        }

        $subscription->update([
            'status' => PlatformSubscriptionStatus::Active,
            'provider_subscription_id' => $this->resolveProviderSubscriptionId($provider, $payload)
                ?? $subscription->provider_subscription_id,
            'metadata' => array_merge($subscription->metadata ?? [], ['webhook' => $payload]),
        ]);

        $subscription->tenant()->update([
            'plan' => $subscription->plan_slug,
            'billing_provider' => $provider->value,
        ]);

        return $subscription->fresh(['tenant']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveProviderSubscriptionId(BillingProvider $provider, array $payload): ?string
    {
        return match ($provider) {
            BillingProvider::Paystack => data_get($payload, 'reference') ?? data_get($payload, 'data.reference'),
            BillingProvider::PayPal => data_get($payload, 'resource.id') ?? data_get($payload, 'id'),
            BillingProvider::Flutterwave => (string) (data_get($payload, 'data.id') ?? data_get($payload, 'id') ?? ''),
        };
    }
}
