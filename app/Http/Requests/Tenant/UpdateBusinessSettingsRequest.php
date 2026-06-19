<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates business settings updates.
 */
class UpdateBusinessSettingsRequest extends FormRequest
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
            'business_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'business_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'business_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'business_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'support_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'support_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'state_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'city_id' => ['sometimes', 'nullable', 'integer'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address_line_1' => ['sometimes', 'nullable', 'string'],
            'address_line_2' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
