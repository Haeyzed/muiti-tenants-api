<?php

declare(strict_types=1);

namespace App\Services\Central\Billing;

use App\Contracts\Central\BillingDriverInterface;
use App\Enums\Central\BillingProvider;
use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use RuntimeException;

/**
 * Local stub billing when no payment provider is configured.
 */
class StubBillingDriver implements BillingDriverInterface
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
        return false;
    }

    /**
     * Get a summary of the tenant's subscription.
     *
     * @param  Tenant  $tenant
     * @return array<string, mixed>
     */
    public function subscriptionSummary(Tenant $tenant): array
    {
        return [
            'provider' => $tenant->billing_provider,
            'plan' => $tenant->plan,
            'on_trial' => $tenant->onTrial(),
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'subscribed' => filled($tenant->plan),
            'subscription' => null,
        ];
    }

    /**
     * Subscribe a tenant to a plan.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @param  string|null  $paymentMethod
     * @return array<string, mixed>
     */
    public function subscribe(Tenant $tenant, Plan $plan, ?string $paymentMethod = null): array
    {
        $trialDays = (int) config('billing.trial_days', 14);

        $tenant->update([
            'plan' => $plan->slug,
            'trial_ends_at' => $tenant->trial_ends_at ?? now()->addDays($trialDays),
        ]);

        return [
            'mode' => 'stub',
            'plan' => $plan->slug,
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'message' => 'Payment provider is not configured. Subscription recorded locally.',
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
        throw new RuntimeException('Tenant has no active provider subscription in stub mode.');
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
        throw new RuntimeException('Tenant has no active provider subscription in stub mode.');
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
            'url' => config('app.url').'/billing/portal-stub?tenant='.$tenant->id,
        ];
    }

    /**
     * Swap a tenant's plan.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @return array<string, mixed>
     */
    public function swapPlan(Tenant $tenant, Plan $plan): array
    {
        $tenant->update(['plan' => $plan->slug]);

        return $this->subscriptionSummary($tenant->fresh());
    }
}
