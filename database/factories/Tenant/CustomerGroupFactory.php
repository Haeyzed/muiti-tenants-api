<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\CustomerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerGroup>
 */
class CustomerGroupFactory extends Factory
{
    protected $model = CustomerGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'discount_percent' => fake()->randomFloat(2, 0, 20),
            'is_active' => true,
        ];
    }
}
