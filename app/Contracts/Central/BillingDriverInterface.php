<?php

declare(strict_types=1);

namespace App\Contracts\Central;

use App\Enums\Central\BillingProvider;
use App\Models\Central\Plan;
use App\Models\Central\Tenant;

/**
 * Provider-specific subscription billing operations.
 */
interface BillingDriverInterface
{
    public function provider(): BillingProvider;

    public function isConfigured(): bool;

    /**
     * @return array<string, mixed>
     */
    public function subscriptionSummary(Tenant $tenant): array;

    /**
     * @return array<string, mixed>
     */
    public function subscribe(Tenant $tenant, Plan $plan, ?string $paymentMethod = null): array;

    public function cancel(Tenant $tenant, bool $immediately = false): Tenant;

    public function resume(Tenant $tenant): Tenant;

    /**
     * @return array<string, mixed>
     */
    public function billingPortalUrl(Tenant $tenant): array;

    /**
     * @return array<string, mixed>
     */
    public function swapPlan(Tenant $tenant, Plan $plan): array;
}
