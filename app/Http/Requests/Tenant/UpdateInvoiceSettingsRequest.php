<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates invoice settings updates.
 */
class UpdateInvoiceSettingsRequest extends FormRequest
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
            'prefix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'number_format' => ['sometimes', 'nullable', 'string', 'max:50'],
            'footer' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'next_sequence' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
