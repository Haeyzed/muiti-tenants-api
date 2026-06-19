<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\OnboardingStep;
use App\Models\Tenant\OnboardingProgress;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages store onboarding wizard progress.
 */
class OnboardingService
{
    /**
     * Get the current onboarding progress.
     *
     * @return OnboardingProgress
     */
    public function getProgress(): OnboardingProgress
    {
        return OnboardingProgress::current();
    }

    /**
     * Get all onboarding steps with their status.
     *
     * @return list<array{step: string, label: string, completed: bool, current: bool}>
     */
    public function getSteps(): array
    {
        $progress = $this->getProgress();
        $completedSteps = $progress->completed_steps ?? [];

        return array_map(function (OnboardingStep $step) use ($progress, $completedSteps): array {
            return [
                'step' => $step->value,
                'label' => str($step->value)->headline()->toString(),
                'completed' => in_array($step->value, $completedSteps, true),
                'current' => $progress->current_step === $step,
            ];
        }, OnboardingStep::ordered());
    }

    /**
     * Mark an onboarding step as completed.
     *
     * @param OnboardingStep $step
     * @return OnboardingProgress
     * @throws Throwable
     */
    public function completeStep(OnboardingStep $step): OnboardingProgress
    {
        return DB::transaction(function () use ($step): OnboardingProgress {
            $progress = OnboardingProgress::current();
            $completedSteps = $progress->completed_steps ?? [];

            if (!in_array($step->value, $completedSteps, true)) {
                $completedSteps[] = $step->value;
            }

            $nextStep = $step->next();

            $progress->update([
                'completed_steps' => $completedSteps,
                'current_step' => $nextStep ?? OnboardingStep::Complete,
            ]);

            return $progress->fresh();
        });
    }

    /**
     * Finish the onboarding process entirely.
     *
     * @return OnboardingProgress
     * @throws Throwable
     */
    public function finishOnboarding(): OnboardingProgress
    {
        return DB::transaction(function (): OnboardingProgress {
            $progress = OnboardingProgress::current();
            $allSteps = array_map(
                fn(OnboardingStep $step): string => $step->value,
                array_filter(OnboardingStep::ordered(), fn(OnboardingStep $step): bool => $step !== OnboardingStep::Complete),
            );

            $progress->update([
                'completed_steps' => $allSteps,
                'current_step' => OnboardingStep::Complete,
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            return $progress->fresh();
        });
    }
}
