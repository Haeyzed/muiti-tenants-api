<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\OnboardingProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OnboardingProgress
 */
class OnboardingProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current_step' => $this->current_step?->value,
            'completed_steps' => $this->completed_steps ?? [],
            'is_completed' => $this->is_completed,
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
