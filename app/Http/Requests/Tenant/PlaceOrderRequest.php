<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates order placement from cart.
 */
class PlaceOrderRequest extends FormRequest
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
            'currency' => ['sometimes', 'string', 'size:3'],
            'shipping_total' => ['sometimes', 'numeric', 'min:0'],
            'tax_total' => ['sometimes', 'numeric', 'min:0'],
            'discount_total' => ['sometimes', 'numeric', 'min:0'],
            'flash_sale_id' => ['nullable', 'integer', Rule::exists('flash_sales', 'id')],
            'checkout_session_token' => ['nullable', 'string', 'max:64'],
            'addresses' => ['sometimes', 'array'],
            'addresses.*.type' => ['required_with:addresses', 'string', 'in:shipping,billing'],
            'addresses.*.first_name' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.last_name' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.phone' => ['nullable', 'string', 'max:30'],
            'addresses.*.address_line_1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.state' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['required_with:addresses', 'string', 'max:20'],
            'addresses.*.country' => ['required_with:addresses', 'string', 'size:2'],
        ];
    }
}
