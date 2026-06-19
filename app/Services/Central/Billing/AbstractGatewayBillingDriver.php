<?php

declare(strict_types=1);

namespace App\Services\Central\Billing;

use App\Contracts\Central\BillingDriverInterface;
use App\Enums\Central\BillingProvider;
use App\Enums\Central\PlatformSubscriptionStatus;
use App\Models\Central\Plan;
use App\Models\Central\PlatformSubscription;
use App\Models\Central\Tenant;
use RuntimeException;

/**
 * Shared subscription persistence for Paystack, PayPal, and Flutterwave.
 */
abstract class AbstractGatewayBillingDriver implements BillingDriverInterface
{
    /**
     * Create a checkout session on the provider.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @param  PlatformSubscription  $subscription
     * @return array
     */
    abstract protected function createCheckout(Tenant $tenant, Plan $plan, PlatformSubscription $subscription): array;

    /**
     * Cancel the subscription on the provider.
     *
     * @param  PlatformSubscription  $subscription
     * @param  bool  $immediately
     * @return void
     */
    abstract protected function cancelOnProvider(PlatformSubscription $subscription, bool $immediately): void;

    /**
     * Resume a canceled subscription on the provider.
     *
     * @param  PlatformSubscription  $subscription
     * @return void
     */
    abstract protected function resumeOnProvider(PlatformSubscription $subscription): void;

    /**
     * Swap the subscription to a new plan on the provider.
     *
     * @param  PlatformSubscription  $subscription
     * @param  Plan  $plan
     * @return void
     */
    abstract protected function swapOnProvider(PlatformSubscription $subscription, Plan $plan): void;

    /**
     * Get the billing portal URL from the provider.
     *
     * @param  PlatformSubscription  $subscription
     * @return string
     */
    abstract protected function billingPortalOnProvider(PlatformSubscription $subscription): string;

    /**
     * Get a summary of the tenant's subscription.
     *
     * @param  Tenant  $tenant
     * @return array
     */
    public function subscriptionSummary(Tenant $tenant): array
    {
        $subscription = $this->subscriptionFor($tenant);

        return [
            'provider' => $this->provider()->value,
            'plan' => $tenant->plan,
            'on_trial' => $subscription?->status === PlatformSubscriptionStatus::Trialing || $tenant->onTrial(),
            'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String() ?? $tenant->trial_ends_at?->toIso8601String(),
            'subscribed' => $subscription?->isActive() ?? filled($tenant->plan),
            'subscription' => $subscription ? $this->formatSubscription($subscription) : null,
        ];
    }

    /**
     * Subscribe a tenant to a plan.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @param  string|null  $paymentMethod
     * @return array
     */
    public function subscribe(Tenant $tenant, Plan $plan, ?string $paymentMethod = null): array
    {
        $providerPlanId = $plan->priceIdFor($this->provider());

        if ($providerPlanId === null && $this->requiresProviderPlanId()) {
            throw new RuntimeException("{$this->provider()->value} plan is not configured for plan [{$plan->slug}].");
        }

        $trialDays = (int) config('billing.trial_days', 14);

        $subscription = PlatformSubscription::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'provider' => $this->provider(),
            ],
            [
                'plan_slug' => $plan->slug,
                'provider_plan_id' => $providerPlanId,
                'status' => PlatformSubscriptionStatus::Pending,
                'trial_ends_at' => $tenant->trial_ends_at ?? now()->addDays($trialDays),
                'ends_at' => null,
                'metadata' => array_filter(['payment_method' => $paymentMethod]),
            ],
        );

        $checkout = $this->createCheckout($tenant, $plan, $subscription);

        $subscription->update([
            'authorization_url' => $checkout['authorization_url'] ?? null,
            'provider_customer_id' => $checkout['provider_customer_id'] ?? $subscription->provider_customer_id,
            'provider_subscription_id' => $checkout['provider_subscription_id'] ?? $subscription->provider_subscription_id,
            'metadata' => array_merge($subscription->metadata ?? [], $checkout['metadata'] ?? []),
        ]);

        $tenant->update([
            'plan' => $plan->slug,
            'billing_provider' => $this->provider()->value,
            'trial_ends_at' => $subscription->trial_ends_at,
        ]);

        return [
            'mode' => 'checkout',
            'provider' => $this->provider()->value,
            'plan' => $plan->slug,
            'authorization_url' => $subscription->authorization_url,
            'reference' => $checkout['reference'] ?? null,
        ];
    }

    /**
     * Cancel a tenant's subscription.
     *
     * @param  Tenant  $tenant
     * @param  bool  $immediately
     * @return Tenant
     */
    public function cancel(Tenant $tenant, bool $immediately = false): Tenant
    {
        $subscription = $this->requireSubscription($tenant);

        $this->cancelOnProvider($subscription, $immediately);

        $subscription->update([
            'status' => PlatformSubscriptionStatus::Cancelled,
            'ends_at' => $immediately ? now() : ($subscription->ends_at ?? now()->addMonth()),
        ]);

        return $tenant->fresh();
    }

    /**
     * Resume a tenant's subscription.
     *
     * @param  Tenant  $tenant
     * @return Tenant
     */
    public function resume(Tenant $tenant): Tenant
    {
        $subscription = $this->requireSubscription($tenant);

        $this->resumeOnProvider($subscription);

        $subscription->update([
            'status' => PlatformSubscriptionStatus::Active,
            'ends_at' => null,
        ]);

        return $tenant->fresh();
    }

    /**
     * Get the URL for the tenant's billing portal.
     *
     * @param  Tenant  $tenant
     * @return array
     */
    public function billingPortalUrl(Tenant $tenant): array
    {
        $subscription = $this->subscriptionFor($tenant);

        if ($subscription === null) {
            throw new RuntimeException('Tenant has no '.$this->provider()->value.' subscription.');
        }

        return [
            'url' => $this->billingPortalOnProvider($subscription),
        ];
    }

    /**
     * Swap the tenant to a different plan.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @return array
     */
    public function swapPlan(Tenant $tenant, Plan $plan): array
    {
        $subscription = $this->requireSubscription($tenant);
        $providerPlanId = $plan->priceIdFor($this->provider());

        if ($providerPlanId === null) {
            throw new RuntimeException("{$this->provider()->value} plan is not configured for plan [{$plan->slug}].");
        }

        $this->swapOnProvider($subscription, $plan);

        $subscription->update([
            'plan_slug' => $plan->slug,
            'provider_plan_id' => $providerPlanId,
        ]);

        $tenant->update([
            'plan' => $plan->slug,
            'billing_provider' => $this->provider()->value,
        ]);

        return $this->subscriptionSummary($tenant->fresh());
    }

    /**
     * Determine if the driver requires a provider plan ID.
     *
     * @return bool
     */
    protected function requiresProviderPlanId(): bool
    {
        return true;
    }

    /**
     * Get the subscription for the tenant.
     *
     * @param  Tenant  $tenant
     * @return PlatformSubscription|null
     */
    protected function subscriptionFor(Tenant $tenant): ?PlatformSubscription
    {
        return PlatformSubscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider', $this->provider())
            ->latest('id')
            ->first();
    }

    /**
     * Get the subscription for the tenant, throwing an exception if not found.
     *
     * @param  Tenant  $tenant
     * @return PlatformSubscription
     * @throws RuntimeException
     */
    protected function requireSubscription(Tenant $tenant): PlatformSubscription
    {
        $subscription = $this->subscriptionFor($tenant);

        if ($subscription === null) {
            throw new RuntimeException('Tenant has no active '.$this->provider()->value.' subscription.');
        }

        return $subscription;
    }

    /**
     * Get the billing email address for the tenant.
     *
     * @param  Tenant  $tenant
     * @return string
     */
    protected function billingEmail(Tenant $tenant): string
    {
        if (filled($tenant->email)) {
            return $tenant->email;
        }

        $owner = $tenant->owner;

        if (is_array($owner) && filled($owner['email'] ?? null)) {
            return (string) $owner['email'];
        }

        return "billing+{$tenant->slug}@".parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    /**
     * Format the subscription array.
     *
     * @param  PlatformSubscription  $subscription
     * @return array<string, mixed>
     */
    protected function formatSubscription(PlatformSubscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'provider' => $subscription->provider->value,
            'provider_subscription_id' => $subscription->provider_subscription_id,
            'plan' => $subscription->plan_slug,
            'status' => $subscription->status->value,
            'authorization_url' => $subscription->authorization_url,
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
        ];
    }
}
