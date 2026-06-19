<?php

declare(strict_types=1);

namespace App\Services\Central\Billing;

use App\Contracts\Central\BillingDriverInterface;
use App\Enums\Central\BillingProvider;
use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Exceptions\SubscriptionUpdateFailure;
use Laravel\Cashier\Subscription;
use RuntimeException;

/**
 * Stripe Cashier billing driver for tenant subscriptions.
 */
class StripeBillingDriver implements BillingDriverInterface
{
    /**
     * Get the billing provider.
     *
     * @return BillingProvider
     */
    public function provider(): BillingProvider
    {
        return BillingProvider::Stripe;
    }

    /**
     * Check if the billing driver is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return filled(config('cashier.secret'));
    }

    /**
     * Get a summary of the tenant's subscription.
     *
     * @param  Tenant  $tenant
     * @return array<string, mixed>
     */
    public function subscriptionSummary(Tenant $tenant): array
    {
        $subscription = $tenant->subscription('default');

        return [
            'provider' => BillingProvider::Stripe->value,
            'plan' => $tenant->plan,
            'on_trial' => $tenant->onTrial(),
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'subscribed' => $tenant->subscribed('default'),
            'subscription' => $subscription ? $this->formatSubscription($subscription) : null,
            'stripe_id' => $tenant->stripe_id,
        ];
    }

    /**
     * Subscribe a tenant to a plan.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @param  string|null  $paymentMethod
     * @return array<string, mixed>
     * @throws RuntimeException|IncompletePayment
     */
    public function subscribe(Tenant $tenant, Plan $plan, ?string $paymentMethod = null): array
    {
        $priceId = $plan->stripe_price_id;

        if ($priceId === null) {
            throw new RuntimeException("Stripe price is not configured for plan [{$plan->slug}].");
        }

        if ($paymentMethod !== null) {
            $tenant->createOrGetStripeCustomer();
            $subscription = $tenant->newSubscription('default', $priceId)->create($paymentMethod);
            $tenant->update(['plan' => $plan->slug, 'billing_provider' => BillingProvider::Stripe->value]);

            return [
                'mode' => 'subscription',
                'provider' => BillingProvider::Stripe->value,
                'plan' => $plan->slug,
                'subscription' => $this->formatSubscription($subscription),
            ];
        }

        /** @var Checkout $checkout */
        $checkout = $tenant->newSubscription('default', $priceId)->checkout([
            'success_url' => config('app.url').'/billing/success?tenant='.$tenant->id,
            'cancel_url' => config('app.url').'/billing/cancel?tenant='.$tenant->id,
        ]);

        $tenant->update(['billing_provider' => BillingProvider::Stripe->value]);

        return [
            'mode' => 'checkout',
            'provider' => BillingProvider::Stripe->value,
            'plan' => $plan->slug,
            'checkout_url' => $checkout->url,
        ];
    }

    /**
     * Cancel a tenant's subscription.
     *
     * @param  Tenant  $tenant
     * @param  bool  $immediately
     * @return Tenant
     * @throws RuntimeException
     */
    public function cancel(Tenant $tenant, bool $immediately = false): Tenant
    {
        $subscription = $tenant->subscription('default');

        if ($subscription === null) {
            throw new RuntimeException('Tenant has no active Stripe subscription.');
        }

        if ($immediately) {
            $subscription->cancelNow();
        } else {
            $subscription->cancel();
        }

        return $tenant->fresh();
    }

    /**
     * Resume a tenant's subscription.
     *
     * @param  Tenant  $tenant
     * @return Tenant
     * @throws RuntimeException
     */
    public function resume(Tenant $tenant): Tenant
    {
        $subscription = $tenant->subscription('default');

        if ($subscription === null) {
            throw new RuntimeException('Tenant has no Stripe subscription to resume.');
        }

        $subscription->resume();

        return $tenant->fresh();
    }

    /**
     * Get the URL for the billing portal.
     *
     * @param  Tenant  $tenant
     * @return array<string, mixed>
     */
    public function billingPortalUrl(Tenant $tenant): array
    {
        return [
            'url' => $tenant->billingPortalUrl(config('app.url').'/billing/return'),
        ];
    }

    /**
     * Swap a tenant's plan.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @return array<string, mixed>
     * @throws IncompletePayment
     * @throws SubscriptionUpdateFailure
     */
    public function swapPlan(Tenant $tenant, Plan $plan): array
    {
        $priceId = $plan->stripe_price_id;

        if ($priceId === null) {
            throw new RuntimeException("Stripe price is not configured for plan [{$plan->slug}].");
        }

        $tenant->subscription('default')?->swap($priceId);
        $tenant->update(['plan' => $plan->slug, 'billing_provider' => BillingProvider::Stripe->value]);

        return $this->subscriptionSummary($tenant->fresh());
    }

    /**
     * Format a Stripe subscription.
     *
     * @param  Subscription  $subscription
     * @return array<string, mixed>
     */
    private function formatSubscription(Subscription $subscription): array
    {
        return [
            'stripe_id' => $subscription->stripe_id,
            'stripe_status' => $subscription->stripe_status,
            'stripe_price' => $subscription->stripe_price,
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'on_grace_period' => $subscription->onGracePeriod(),
        ];
    }
}
