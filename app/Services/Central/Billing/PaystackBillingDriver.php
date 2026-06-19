<?php

declare(strict_types=1);

namespace App\Services\Central\Billing;

use App\Enums\Central\BillingProvider;
use App\Models\Central\Plan;
use App\Models\Central\PlatformSubscription;
use App\Models\Central\Tenant;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Paystack billing driver for tenant platform subscriptions.
 */
class PaystackBillingDriver extends AbstractGatewayBillingDriver
{
    /**
     * Get the billing provider.
     *
     * @return BillingProvider
     */
    public function provider(): BillingProvider
    {
        return BillingProvider::Paystack;
    }

    /**
     * Check if the driver is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return filled(config('billing.paystack.secret_key'));
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
     * Create a checkout session on Paystack.
     *
     * @param Tenant $tenant
     * @param Plan $plan
     * @param PlatformSubscription $subscription
     * @return array
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function createCheckout(Tenant $tenant, Plan $plan, PlatformSubscription $subscription): array
    {
        $reference = 'PSK_'.Str::upper(Str::random(16));
        $payload = [
            'email' => $this->billingEmail($tenant),
            'amount' => (int) round(((float) $plan->price) * 100),
            'currency' => strtoupper($plan->currency),
            'reference' => $reference,
            'callback_url' => $this->callbackUrl($tenant),
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan' => $plan->slug,
                'provider' => BillingProvider::Paystack->value,
            ],
        ];

        if (filled($plan->paystack_plan_code)) {
            $payload['plan'] = $plan->paystack_plan_code;
        }

        $response = Http::withToken((string) config('billing.paystack.secret_key'))
            ->acceptJson()
            ->post('https://api.paystack.co/transaction/initialize', $payload)
            ->throw()
            ->json('data');

        return [
            'authorization_url' => $response['authorization_url'] ?? null,
            'reference' => $response['reference'] ?? $reference,
            'provider_subscription_id' => $response['reference'] ?? $reference,
            'metadata' => ['initialize' => $response],
        ];
    }

    /**
     * Cancel the subscription on Paystack.
     *
     * @param PlatformSubscription $subscription
     * @param bool $immediately
     * @return void
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function cancelOnProvider(PlatformSubscription $subscription, bool $immediately): void
    {
        if (! filled($subscription->provider_subscription_id)) {
            return;
        }

        Http::withToken((string) config('billing.paystack.secret_key'))
            ->acceptJson()
            ->post('https://api.paystack.co/subscription/disable', [
                'code' => $subscription->provider_subscription_id,
                'token' => $subscription->metadata['email_token'] ?? null,
            ])
            ->throw();
    }

    /**
     * Resume a canceled subscription on Paystack.
     *
     * @param PlatformSubscription $subscription
     * @return void
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function resumeOnProvider(PlatformSubscription $subscription): void
    {
        if (! filled($subscription->provider_subscription_id)) {
            return;
        }

        Http::withToken((string) config('billing.paystack.secret_key'))
            ->acceptJson()
            ->post('https://api.paystack.co/subscription/enable', [
                'code' => $subscription->provider_subscription_id,
                'token' => $subscription->metadata['email_token'] ?? null,
            ])
            ->throw();
    }

    /**
     * Swap the subscription to a new plan on Paystack.
     *
     * @param  PlatformSubscription  $subscription
     * @param  Plan  $plan
     * @return void
     */
    protected function swapOnProvider(PlatformSubscription $subscription, Plan $plan): void
    {
        if (! filled($plan->paystack_plan_code)) {
            throw new RuntimeException("Paystack plan is not configured for plan [{$plan->slug}].");
        }

        $subscription->update([
            'provider_plan_id' => $plan->paystack_plan_code,
        ]);
    }

    /**
     * Get the billing portal URL from Paystack.
     *
     * @param  PlatformSubscription  $subscription
     * @return string
     */
    protected function billingPortalOnProvider(PlatformSubscription $subscription): string
    {
        return config('app.url').'/billing/paystack/manage?tenant='.$subscription->tenant_id;
    }

    /**
     * Get the callback URL for Paystack checkout.
     *
     * @param  Tenant  $tenant
     * @return string
     */
    private function callbackUrl(Tenant $tenant): string
    {
        return config('app.url').'/billing/callback/paystack?tenant='.$tenant->id;
    }
}
