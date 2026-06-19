<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\CheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CheckoutSession
 */
class CheckoutSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'session_token' => $this->session_token,
            'queue_position' => $this->queue_position,
            'status' => $this->status?->value,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'admitted_at' => $this->admitted_at?->toIso8601String(),
            'is_admitted' => $this->isAdmitted(),
        ];
    }
}
