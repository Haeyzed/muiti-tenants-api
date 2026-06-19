<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\EmploymentStatus;
use App\Enums\Tenant\EmploymentType;
use App\Models\Tenant\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => 'STF-'.strtoupper(Str::random(8)),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'employment_type' => EmploymentType::FullTime,
            'employment_status' => EmploymentStatus::Active,
            'hire_date' => fake()->date(),
            'allow_login' => false,
        ];
    }
}
