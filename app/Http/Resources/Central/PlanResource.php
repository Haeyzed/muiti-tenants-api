<?php

declare(strict_types=1);

namespace App\Http\Resources\Central;

use App\Models\Central\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'interval' => $this->interval,
            'stripe_price_id' => $this->when($request->user()?->can('plans.manage'), $this->stripe_price_id),
            'paddle_price_id' => $this->when($request->user()?->can('plans.manage'), $this->paddle_price_id),
            'paystack_plan_code' => $this->when($request->user()?->can('plans.manage'), $this->paystack_plan_code),
            'paypal_plan_id' => $this->when($request->user()?->can('plans.manage'), $this->paypal_plan_id),
            'flutterwave_plan_id' => $this->when($request->user()?->can('plans.manage'), $this->flutterwave_plan_id),
            'features' => $this->features ?? [],
            'limits' => $this->limits ?? [],
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
