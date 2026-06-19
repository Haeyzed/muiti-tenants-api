<?php

declare(strict_types=1);

namespace Database\Factories\Central;

use App\Models\Central\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'name' => ucwords($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 9, 299),
            'currency' => 'USD',
            'interval' => 'monthly',
            'features' => ['Feature A', 'Feature B'],
            'limits' => ['flash_sales' => 10],
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
