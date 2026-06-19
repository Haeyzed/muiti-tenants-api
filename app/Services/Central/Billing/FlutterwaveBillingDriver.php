<?php

declare(strict_types=1);

namespace App\Services\Central\Billing;

use App\Enums\Central\BillingProvider;
use App\Models\Central\Plan;
use App\Models\Central\PlatformSubscription;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Flutterwave billing driver for tenant platform subscriptions.
 */
class FlutterwaveBillingDriver extends AbstractGatewayBillingDriver
{
    /**
     * Get the billing provider.
     *
     * @return BillingProvider
     */
    public function provider(): BillingProvider
    {
        return BillingProvider::Flutterwave;
    }

    /**
     * Check if the driver is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return filled(config('billing.flutterwave.secret_key'));
    }

    /**
     * Determine if the driver requires a provider plan ID.
     *
     * @return bool
     */
    protected function requiresProviderPlanId(): bool
    {
        return false;
    }

    /**
     * Create a checkout session on Flutterwave.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @param  PlatformSubscription  $subscription
     * @return array
     */
    protected function createCheckout(Tenant $tenant, Plan $plan, PlatformSubscription $subscription): array
    {
        $reference = 'FLW_'.Str::upper(Str::random(16));

        $payload = [
            'tx_ref' => $reference,
            'amount' => (float) $plan->price,
            'currency' => strtoupper($plan->currency),
            'redirect_url' => $this->callbackUrl($tenant),
            'customer' => [
                'email' => $this->billingEmail($tenant),
                'name' => $tenant->name,
            ],
            'meta' => [
                'tenant_id' => $tenant->id,
                'plan' => $plan->slug,
                'provider' => BillingProvider::Flutterwave->value,
            ],
        ];

        if (filled($plan->flutterwave_plan_id)) {
            $payload['payment_plan'] = (int) $plan->flutterwave_plan_id;
        }

        $response = Http::withToken((string) config('billing.flutterwave.secret_key'))
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', $payload)
            ->throw()
            ->json('data');

        return [
            'authorization_url' => $response['link'] ?? null,
            'reference' => $reference,
            'provider_subscription_id' => (string) ($response['id'] ?? $reference),
            'metadata' => ['initialize' => $response],
        ];
    }

    /**
     * Cancel the subscription on Flutterwave.
     *
     * @param  PlatformSubscription  $subscription
     * @param  bool  $immediately
     * @return void
     */
    protected function cancelOnProvider(PlatformSubscription $subscription, bool $immediately): void
    {
        if (! filled($subscription->provider_plan_id)) {
            return;
        }

        Http::withToken((string) config('billing.flutterwave.secret_key'))
            ->acceptJson()
            ->put('https://api.flutterwave.com/v3/payment-plans/'.$subscription->provider_plan_id.'/cancel')
            ->throw();
    }

    /**
     * Resume a canceled subscription on Flutterwave.
     *
     * @param  PlatformSubscription  $subscription
     * @return void
     */
    protected function resumeOnProvider(PlatformSubscription $subscription): void
    {
        // Flutterwave does not expose a direct resume endpoint for cancelled plans.
    }

    /**
     * Swap the subscription to a new plan on Flutterwave.
     *
     * @param  PlatformSubscription  $subscription
     * @param  Plan  $plan
     * @return void
     */
    protected function swapOnProvider(PlatformSubscription $subscription, Plan $plan): void
    {
        if (! filled($plan->flutterwave_plan_id)) {
            throw new RuntimeException("Flutterwave plan is not configured for plan [{$plan->slug}].");
        }

        $subscription->update([
            'provider_plan_id' => $plan->flutterwave_plan_id,
        ]);
    }

    /**
     * Get the billing portal URL from Flutterwave.
     *
     * @param  PlatformSubscription  $subscription
     * @return string
     */
    protected function billingPortalOnProvider(PlatformSubscription $subscription): string
    {
        return config('app.url').'/billing/flutterwave/manage?tenant='.$subscription->tenant_id;
    }

    /**
     * Get the callback URL for Flutterwave.
     *
     * @param  Tenant  $tenant
     * @return string
     */
    private function callbackUrl(Tenant $tenant): string
    {
        return config('app.url').'/billing/callback/flutterwave?tenant='.$tenant->id;
    }
}
