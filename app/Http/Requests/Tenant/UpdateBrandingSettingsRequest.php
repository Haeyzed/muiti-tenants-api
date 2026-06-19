<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates branding settings updates.
 */
class UpdateBrandingSettingsRequest extends FormRequest
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
            'theme' => ['sometimes', 'nullable', 'array'],
            'store_logo' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'store_banner' => ['sometimes', 'nullable', 'image', 'max:10240'],
            'favicon' => ['sometimes', 'nullable', 'image', 'max:1024'],
        ];
    }
}
