<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\OnboardingStep;
use Illuminate\Database\Eloquent\Model;

/**
 * Tracks store onboarding wizard progress.
 */
class OnboardingProgress extends Model
{
    protected $table = 'onboarding_progress';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'current_step',
        'completed_steps',
        'is_completed',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_step' => OnboardingStep::class,
            'completed_steps' => 'array',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the current onboarding progress record or create it.
     *
     * @return self
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'current_step' => OnboardingStep::BusinessInfo,
            'completed_steps' => [],
            'is_completed' => false,
        ]);
    }

    /**
     * Check if a specific onboarding step is completed.
     *
     * @param  OnboardingStep  $step
     * @return bool
     */
    public function isStepCompleted(OnboardingStep $step): bool
    {
        return in_array($step->value, $this->completed_steps ?? [], true);
    }
}
