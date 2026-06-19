<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Staff
 */
class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'staff_id' => $this->staff_id,
            'employee_number' => $this->employee_number,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'employment_type' => $this->employment_type?->value,
            'employment_status' => $this->employment_status?->value,
            'hire_date' => $this->hire_date?->toDateString(),
            'termination_date' => $this->termination_date?->toDateString(),
            'allow_login' => $this->allow_login,
            'department' => $this->whenLoaded('department', fn () => new DepartmentResource($this->department)),
            'position' => $this->whenLoaded('position', fn () => new PositionResource($this->position)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
