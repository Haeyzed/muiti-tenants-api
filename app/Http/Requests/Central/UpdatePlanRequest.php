<?php

declare(strict_types=1);

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates plan update requests.
 */
class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \App\Models\Central\Plan $plan */
        $plan = $this->route('plan');

        return [
            'slug' => ['sometimes', 'string', 'max:100', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($plan->id)],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'interval' => ['sometimes', 'string', Rule::in(['monthly', 'yearly'])],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'paddle_price_id' => ['nullable', 'string', 'max:255'],
            'paystack_plan_code' => ['nullable', 'string', 'max:255'],
            'paypal_plan_id' => ['nullable', 'string', 'max:255'],
            'flutterwave_plan_id' => ['nullable', 'string', 'max:255'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'limits' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
