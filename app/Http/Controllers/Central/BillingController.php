<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Enums\Central\BillingProvider;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Central\SubscribeTenantRequest;
use App\Http\Resources\Central\PlanResource;
use App\Models\Central\Tenant;
use App\Services\Central\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Manages platform subscription billing for tenants.
 */
class BillingController extends ApiController
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * Get a list of available active subscription plans.
     *
     * @return JsonResponse
     */
    public function plans(): JsonResponse
    {
        abort_unless(request()->user()?->can('billing.view'), 403);

        return $this->success(PlanResource::collection($this->billingService->plans()), 'Plans retrieved successfully.');
    }

    /**
     * Get the subscription summary for a given tenant.
     *
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function subscription(Tenant $tenant): JsonResponse
    {
        $this->authorize('viewBilling', $tenant);

        return $this->success($this->billingService->subscriptionSummary($tenant), 'Subscription retrieved successfully.');
    }

    /**
     * Subscribe a tenant to a specific plan.
     *
     * @param  SubscribeTenantRequest  $request
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function subscribe(SubscribeTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('manageBilling', $tenant);

        try {
            $provider = BillingProvider::from($request->string('provider', config('billing.default_provider', BillingProvider::Stripe->value))->toString());
            $result = $this->billingService->subscribe(
                $tenant,
                $request->validated('plan'),
                $provider,
                $request->validated('payment_method'),
            );
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        return $this->created($result, 'Subscription initiated successfully.');
    }

    /**
     * Cancel a tenant's active subscription.
     *
     * @param  Request  $request
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function cancel(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('manageBilling', $tenant);

        try {
            $tenant = $this->billingService->cancel($tenant, $request->boolean('immediately'));
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->updated(
            $this->billingService->subscriptionSummary($tenant),
            'Subscription cancelled successfully.',
        );
    }

    /**
     * Generate a billing portal URL for the tenant.
     *
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function portal(Tenant $tenant): JsonResponse
    {
        $this->authorize('manageBilling', $tenant);

        return $this->success($this->billingService->billingPortalUrl($tenant), 'Billing portal URL retrieved successfully.');
    }

    /**
     * Swap the tenant's current plan for a new one.
     *
     * @param  SubscribeTenantRequest  $request
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function swap(SubscribeTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('manageBilling', $tenant);

        try {
            $summary = $this->billingService->swapPlan($tenant, $request->validated('plan'), BillingProvider::tryFrom($request->string('provider')->toString()));
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->updated($summary, 'Subscription plan updated successfully.');
    }
}
