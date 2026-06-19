<?php

declare(strict_types=1);

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates tenant creation requests.
 */
class StoreTenantRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:tenants,slug'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'plan' => ['nullable', 'string', 'max:100', Rule::exists('plans', 'slug')],
            'trial_ends_at' => ['nullable', 'date'],
            'subdomain' => ['nullable', 'string', 'max:63', 'alpha_dash'],
            'owner' => ['required', 'array'],
            'owner.name' => ['required', 'string', 'max:255'],
            'owner.email' => ['required', 'email', 'max:255'],
            'owner.phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
