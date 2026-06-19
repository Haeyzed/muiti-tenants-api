<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates product variant creation requests.
 */
class StoreProductVariantRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'options' => ['nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
            'inventory' => ['nullable', 'array'],
            'inventory.quantity' => ['nullable', 'integer', 'min:0'],
            'inventory.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
