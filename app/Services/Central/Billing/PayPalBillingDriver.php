<?php

declare(strict_types=1);

namespace App\Services\Central\Billing;

use App\Enums\Central\BillingProvider;
use App\Models\Central\Plan;
use App\Models\Central\PlatformSubscription;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * PayPal billing driver for tenant platform subscriptions.
 */
class PayPalBillingDriver extends AbstractGatewayBillingDriver
{
    /**
     * Get the billing provider.
     *
     * @return BillingProvider
     */
    public function provider(): BillingProvider
    {
        return BillingProvider::PayPal;
    }

    /**
     * Check if the driver is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return filled(config('billing.paypal.client_id'))
            && filled(config('billing.paypal.client_secret'));
    }

    /**
     * Create a checkout session on PayPal.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @param  PlatformSubscription  $subscription
     * @return array
     */
    protected function createCheckout(Tenant $tenant, Plan $plan, PlatformSubscription $subscription): array
    {
        if (! filled($plan->paypal_plan_id)) {
            throw new RuntimeException("PayPal plan is not configured for plan [{$plan->slug}].");
        }

        $reference = 'PPL_'.Str::upper(Str::random(16));

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->apiBaseUrl().'/v1/billing/subscriptions', [
                'plan_id' => $plan->paypal_plan_id,
                'custom_id' => $tenant->id,
                'subscriber' => [
                    'email_address' => $this->billingEmail($tenant),
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'return_url' => $this->returnUrl($tenant),
                    'cancel_url' => $this->cancelUrl($tenant),
                ],
            ])
            ->throw()
            ->json();

        $approvalLink = collect($response['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        return [
            'authorization_url' => $approvalLink,
            'reference' => $reference,
            'provider_subscription_id' => $response['id'] ?? $reference,
            'metadata' => ['subscription' => $response],
        ];
    }

    /**
     * Cancel the subscription on PayPal.
     *
     * @param  PlatformSubscription  $subscription
     * @param  bool  $immediately
     * @return void
     */
    protected function cancelOnProvider(PlatformSubscription $subscription, bool $immediately): void
    {
        if (! filled($subscription->provider_subscription_id)) {
            return;
        }

        Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->apiBaseUrl().'/v1/billing/subscriptions/'.$subscription->provider_subscription_id.'/cancel', [
                'reason' => $immediately ? 'Immediate cancellation requested.' : 'Cancellation at period end requested.',
            ])
            ->throw();
    }

    /**
     * Resume a canceled subscription on PayPal.
     *
     * @param  PlatformSubscription  $subscription
     * @return void
     */
    protected function resumeOnProvider(PlatformSubscription $subscription): void
    {
        if (! filled($subscription->provider_subscription_id)) {
            return;
        }

        Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->apiBaseUrl().'/v1/billing/subscriptions/'.$subscription->provider_subscription_id.'/activate')
            ->throw();
    }

    /**
     * Swap the subscription to a new plan on PayPal.
     *
     * @param  PlatformSubscription  $subscription
     * @param  Plan  $plan
     * @return void
     */
    protected function swapOnProvider(PlatformSubscription $subscription, Plan $plan): void
    {
        if (! filled($plan->paypal_plan_id)) {
            throw new RuntimeException("PayPal plan is not configured for plan [{$plan->slug}].");
        }

        if (! filled($subscription->provider_subscription_id)) {
            throw new RuntimeException('Tenant has no PayPal subscription to swap.');
        }

        Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->apiBaseUrl().'/v1/billing/subscriptions/'.$subscription->provider_subscription_id.'/revise', [
                'plan_id' => $plan->paypal_plan_id,
            ])
            ->throw();

        $subscription->update([
            'provider_plan_id' => $plan->paypal_plan_id,
        ]);
    }

    /**
     * Get the billing portal URL from PayPal.
     *
     * @param  PlatformSubscription  $subscription
     * @return string
     */
    protected function billingPortalOnProvider(PlatformSubscription $subscription): string
    {
        return config('app.url').'/billing/paypal/manage?tenant='.$subscription->tenant_id;
    }

    /**
     * Get the PayPal API access token.
     *
     * @return string
     */
    private function accessToken(): string
    {
        return Cache::remember('billing.paypal.access_token', 3000, function (): string {
            $response = Http::asForm()
                ->withBasicAuth(
                    (string) config('billing.paypal.client_id'),
                    (string) config('billing.paypal.client_secret'),
                )
                ->post($this->apiBaseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ])
                ->throw()
                ->json();

            return (string) ($response['access_token'] ?? '');
        });
    }

    /**
     * Get the PayPal API base URL.
     *
     * @return string
     */
    private function apiBaseUrl(): string
    {
        return config('billing.paypal.sandbox', true)
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * Get the return URL for PayPal checkout.
     *
     * @param  Tenant  $tenant
     * @return string
     */
    private function returnUrl(Tenant $tenant): string
    {
        return config('app.url').'/billing/callback/paypal?tenant='.$tenant->id;
    }

    /**
     * Get the cancel URL for PayPal checkout.
     *
     * @param  Tenant  $tenant
     * @return string
     */
    private function cancelUrl(Tenant $tenant): string
    {
        return config('app.url').'/billing/cancel/paypal?tenant='.$tenant->id;
    }
}
