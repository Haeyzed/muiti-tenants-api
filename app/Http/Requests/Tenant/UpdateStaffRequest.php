<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\EmploymentStatus;
use App\Enums\Tenant\EmploymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates staff updates.
 */
class UpdateStaffRequest extends FormRequest
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
        /** @var \App\Models\Tenant\Staff|null $staff */
        $staff = $this->route('staff');

        return [
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'staff_id' => ['nullable', 'string', 'max:50', Rule::unique('staff', 'staff_id')->ignore($staff?->id)],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'hire_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'allow_login' => ['sometimes', 'boolean'],
        ];
    }
}
