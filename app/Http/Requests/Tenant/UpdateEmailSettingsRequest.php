<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates email settings updates.
 */
class UpdateEmailSettingsRequest extends FormRequest
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
            'sender_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sender_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'smtp_host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_password' => ['sometimes', 'nullable', 'string'],
            'smtp_encryption' => ['sometimes', 'nullable', 'string', 'in:tls,ssl,null'],
            'templates' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
