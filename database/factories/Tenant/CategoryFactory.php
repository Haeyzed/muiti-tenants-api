<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
