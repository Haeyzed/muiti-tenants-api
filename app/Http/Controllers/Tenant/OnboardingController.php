<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\Tenant\OnboardingStep;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Tenant\OnboardingProgressResource;
use App\Models\Tenant\OnboardingProgress;
use App\Services\Tenant\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Manages store onboarding wizard.
 */
class OnboardingController extends ApiController
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {}

    /**
     * Show the current onboarding progress.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OnboardingProgress::class);

        $progress = $this->onboardingService->getProgress();

        return $this->successResponse([
            'progress' => new OnboardingProgressResource($progress),
            'steps' => $this->onboardingService->getSteps(),
        ]);
    }

    /**
     * Complete a specific onboarding step.
     *
     * @param  Request  $request
     * @param  string  $step
     * @return JsonResponse
     */
    public function completeStep(Request $request, string $step): JsonResponse
    {
        $progress = $this->onboardingService->getProgress();
        $this->authorize('update', $progress);

        $onboardingStep = OnboardingStep::from($step);
        $progress = $this->onboardingService->completeStep($onboardingStep);

        return $this->successResponse(
            new OnboardingProgressResource($progress),
            'Onboarding step completed.',
        );
    }

    /**
     * Finish the onboarding process.
     *
     * @param  Request  $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function finish(Request $request): JsonResponse
    {
        $progress = $this->onboardingService->getProgress();
        $this->authorize('update', $progress);

        $progress = $this->onboardingService->finishOnboarding();

        return $this->successResponse(
            new OnboardingProgressResource($progress),
            'Onboarding completed successfully.',
        );
    }
}
