<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Central\StorePlanRequest;
use App\Http\Requests\Central\UpdatePlanRequest;
use App\Http\Resources\Central\PlanResource;
use App\Models\Central\Plan;
use App\Services\Central\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Manages platform subscription plans.
 */
class PlanController extends ApiController
{
    public function __construct(
        private readonly PlanService $planService,
    ) {}

    /**
     * Get a paginated list of plans.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Plan::class);

        $filters = $request->validate([
            'search'    => ['nullable', 'string'],
            'is_active'    => ['nullable', 'array'],
            'is_active.*'  => ['string', 'in:active,inactive'],
        ]);

        $plans = $this->planService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($plans, PlanResource::collection($plans), 'Plans retrieved successfully.');
    }

    /**
     * Store a newly created plan in storage.
     *
     * @param  StorePlanRequest  $request
     * @return JsonResponse
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        $this->authorize('create', Plan::class);

        $plan = $this->planService->create($request->validated());

        return $this->created(
            new PlanResource($plan),
            'Plan created successfully.',
        );
    }

    /**
     * Display the specified plan.
     *
     * @param  Plan  $plan
     * @return JsonResponse
     */
    public function show(Plan $plan): JsonResponse
    {
        $this->authorize('view', $plan);

        return $this->success(new PlanResource($this->planService->find($plan->id)), 'Plan retrieved successfully.');
    }

    /**
     * Update the specified plan in storage.
     *
     * @param  UpdatePlanRequest  $request
     * @param  Plan  $plan
     * @return JsonResponse
     */
    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $this->authorize('update', $plan);

        $plan = $this->planService->update($plan, $request->validated());

        return $this->updated(
            new PlanResource($plan),
            'Plan updated successfully.',
        );
    }

    /**
     * Remove the specified plan from storage.
     *
     * @param  Plan  $plan
     * @return JsonResponse
     */
    public function destroy(Plan $plan): JsonResponse
    {
        $this->authorize('delete', $plan);

        try {
            $this->planService->delete($plan);
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        return $this->deleted('Plan deleted successfully.');
    }

    /**
     * Get plan options.
     *
     * @return JsonResponse
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Plan::class);

        return $this->success($this->planService->getOptions(), 'Plan options retrieved successfully.');
    }
}
