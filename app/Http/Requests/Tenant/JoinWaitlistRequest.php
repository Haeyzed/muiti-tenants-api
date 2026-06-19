<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\WaitlistType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates waitlist subscription requests.
 */
class JoinWaitlistRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(WaitlistType::values())],
            'flash_sale_id' => ['nullable', 'integer', Rule::exists('flash_sales', 'id')],
        ];
    }
}
