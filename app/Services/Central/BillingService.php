<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Contracts\Central\BillingDriverInterface;
use App\Enums\Central\BillingProvider;
use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use App\Services\Central\Billing\FlutterwaveBillingDriver;
use App\Services\Central\Billing\PaddleBillingDriver;
use App\Services\Central\Billing\PayPalBillingDriver;
use App\Services\Central\Billing\PaystackBillingDriver;
use App\Services\Central\Billing\StripeBillingDriver;
use App\Services\Central\Billing\StubBillingDriver;
use Illuminate\Support\Collection;

/**
 * Orchestrates tenant subscription billing across supported providers.
 */
class BillingService
{
    public function __construct(
        private readonly PlanService $planService,
        private readonly StripeBillingDriver $stripeDriver,
        private readonly PaddleBillingDriver $paddleDriver,
        private readonly PaystackBillingDriver $paystackDriver,
        private readonly PayPalBillingDriver $paypalDriver,
        private readonly FlutterwaveBillingDriver $flutterwaveDriver,
        private readonly StubBillingDriver $stubDriver,
    ) {}

    /**
     * Get active subscription plans.
     *
     * @return Collection<int, Plan>
     */
    public function plans(): Collection
    {
        return $this->planService->activePlans();
    }

    /**
     * Get a summary of the tenant's subscription.
     *
     * @param Tenant $tenant
     * @return array<string, mixed>
     */
    public function subscriptionSummary(Tenant $tenant): array
    {
        return $this->driverForTenant($tenant)->subscriptionSummary($tenant);
    }

    /**
     * Subscribe a tenant to a plan.
     *
     * @param Tenant $tenant
     * @param string $planSlug
     * @param BillingProvider $provider
     * @param string|null $paymentMethod
     * @return array<string, mixed>
     */
    public function subscribe(Tenant $tenant, string $planSlug, BillingProvider $provider, ?string $paymentMethod = null): array
    {
        $plan = $this->planService->findBySlug($planSlug);
        $driver = $this->driverForProvider($provider);

        if (!$driver->isConfigured()) {
            $result = $this->stubDriver->subscribe($tenant, $plan, $paymentMethod);
            $tenant->update(['billing_provider' => $provider->value]);

            return $result;
        }

        return $driver->subscribe($tenant, $plan, $paymentMethod);
    }

    /**
     * Cancel a tenant's subscription.
     *
     * @param Tenant $tenant
     * @param bool $immediately
     * @return Tenant
     */
    public function cancel(Tenant $tenant, bool $immediately = false): Tenant
    {
        return $this->driverForTenant($tenant)->cancel($tenant, $immediately);
    }

    /**
     * Resume a tenant's subscription.
     *
     * @param Tenant $tenant
     * @return Tenant
     */
    public function resume(Tenant $tenant): Tenant
    {
        return $this->driverForTenant($tenant)->resume($tenant);
    }

    /**
     * Get the URL for the billing portal.
     *
     * @param Tenant $tenant
     * @return array<string, mixed>
     */
    public function billingPortalUrl(Tenant $tenant): array
    {
        $driver = $this->driverForTenant($tenant);

        if (!$driver->isConfigured()) {
            return $this->stubDriver->billingPortalUrl($tenant);
        }

        return $driver->billingPortalUrl($tenant);
    }

    /**
     * Swap a tenant's subscription plan.
     *
     * @param Tenant $tenant
     * @param string $planSlug
     * @param BillingProvider|null $provider
     * @return array<string, mixed>
     */
    public function swapPlan(Tenant $tenant, string $planSlug, ?BillingProvider $provider = null): array
    {
        $plan = $this->planService->findBySlug($planSlug);
        $driver = $provider !== null
            ? $this->driverForProvider($provider)
            : $this->driverForTenant($tenant);

        if (!$driver->isConfigured()) {
            return $this->stubDriver->swapPlan($tenant, $plan);
        }

        return $driver->swapPlan($tenant, $plan);
    }

    /**
     * Get the billing driver for a provider.
     *
     * @param BillingProvider $provider
     * @return BillingDriverInterface
     */
    private function driverForProvider(BillingProvider $provider): BillingDriverInterface
    {
        return match ($provider) {
            BillingProvider::Stripe => $this->stripeDriver,
            BillingProvider::Paddle => $this->paddleDriver,
            BillingProvider::Paystack => $this->paystackDriver,
            BillingProvider::PayPal => $this->paypalDriver,
            BillingProvider::Flutterwave => $this->flutterwaveDriver,
        };
    }

    /**
     * Get the billing driver for a tenant.
     *
     * @param Tenant $tenant
     * @return BillingDriverInterface
     */
    private function driverForTenant(Tenant $tenant): BillingDriverInterface
    {
        $provider = BillingProvider::tryFrom((string)$tenant->billing_provider)
            ?? BillingProvider::from(config('billing.default_provider', BillingProvider::Stripe->value));

        $driver = $this->driverForProvider($provider);

        if (!$driver->isConfigured()) {
            return $this->stubDriver;
        }

        return $driver;
    }
}
