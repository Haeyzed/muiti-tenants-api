<?php

declare(strict_types=1);

namespace App\Services\Central\Billing;

use App\Contracts\Central\BillingDriverInterface;
use App\Enums\Central\BillingProvider;
use App\Models\Central\PaddleBillingAccount;
use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use Laravel\Paddle\Subscription;
use RuntimeException;

/**
 * Paddle Cashier billing driver for tenant subscriptions.
 */
class PaddleBillingDriver implements BillingDriverInterface
{
    /**
     * Get the billing provider.
     *
     * @return BillingProvider
     */
    public function provider(): BillingProvider
    {
        return BillingProvider::Paddle;
    }

    /**
     * Check if the billing driver is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return filled(config('cashier.client_side_token')) && filled(config('cashier.api_key'));
    }

    /**
     * Get a summary of the tenant's subscription.
     *
     * @param  Tenant  $tenant
     * @return array<string, mixed>
     */
    public function subscriptionSummary(Tenant $tenant): array
    {
        $account = $this->accountFor($tenant);
        $subscription = $account?->subscription('default');

        return [
            'provider' => BillingProvider::Paddle->value,
            'plan' => $tenant->plan,
            'on_trial' => $account?->onTrial() ?? $tenant->onTrial(),
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'subscribed' => $account?->subscribed('default') ?? false,
            'subscription' => $subscription ? $this->formatSubscription($subscription) : null,
            'paddle_customer_id' => $account?->customer?->paddle_id,
        ];
    }

    /**
     * Subscribe a tenant to a plan.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @param  string|null  $paymentMethod
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    public function subscribe(Tenant $tenant, Plan $plan, ?string $paymentMethod = null): array
    {
        $priceId = $plan->paddle_price_id;

        if ($priceId === null) {
            throw new RuntimeException("Paddle price is not configured for plan [{$plan->slug}].");
        }

        $account = $this->accountFor($tenant) ?? $this->createAccount($tenant);

        $checkout = $account->subscribe($priceId, 'default')
            ->returnTo(config('app.url').'/billing/success?tenant='.$tenant->id)
            ->customData([
                'tenant_id' => $tenant->id,
                'plan' => $plan->slug,
            ]);

        $tenant->update([
            'plan' => $plan->slug,
            'billing_provider' => BillingProvider::Paddle->value,
        ]);

        return [
            'mode' => 'checkout',
            'provider' => BillingProvider::Paddle->value,
            'plan' => $plan->slug,
            'checkout' => method_exists($checkout, 'toArray') ? $checkout->toArray() : ['transaction_id' => $checkout->id ?? null],
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
        $account = $this->accountFor($tenant);
        $subscription = $account?->subscription('default');

        if ($subscription === null) {
            throw new RuntimeException('Tenant has no active Paddle subscription.');
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
        $subscription = $this->accountFor($tenant)?->subscription('default');

        if ($subscription === null) {
            throw new RuntimeException('Tenant has no Paddle subscription to resume.');
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
            'url' => config('app.url').'/billing/paddle-portal-stub?tenant='.$tenant->id,
        ];
    }

    /**
     * Swap a tenant's plan.
     *
     * @param  Tenant  $tenant
     * @param  Plan  $plan
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    public function swapPlan(Tenant $tenant, Plan $plan): array
    {
        $priceId = $plan->paddle_price_id;

        if ($priceId === null) {
            throw new RuntimeException("Paddle price is not configured for plan [{$plan->slug}].");
        }

        $this->accountFor($tenant)?->subscription('default')?->swap($priceId);
        $tenant->update(['plan' => $plan->slug, 'billing_provider' => BillingProvider::Paddle->value]);

        return $this->subscriptionSummary($tenant->fresh());
    }

    /**
     * Get the Paddle billing account for a tenant.
     *
     * @param  Tenant  $tenant
     * @return PaddleBillingAccount|null
     */
    private function accountFor(Tenant $tenant): ?PaddleBillingAccount
    {
        return $tenant->paddleBillingAccount;
    }

    /**
     * Create a Paddle billing account for a tenant.
     *
     * @param  Tenant  $tenant
     * @return PaddleBillingAccount
     */
    private function createAccount(Tenant $tenant): PaddleBillingAccount
    {
        return PaddleBillingAccount::query()->create([
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Format a Paddle subscription.
     *
     * @param  Subscription  $subscription
     * @return array<string, mixed>
     */
    private function formatSubscription(Subscription $subscription): array
    {
        return [
            'paddle_id' => $subscription->paddle_id,
            'status' => $subscription->status,
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'on_grace_period' => $subscription->onGracePeriod(),
        ];
    }
}
