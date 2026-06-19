<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'price' => fake()->randomFloat(2, 10, 500),
            'compare_at_price' => fake()->optional()->randomFloat(2, 15, 600),
            'meta_title' => fake()->sentence(4),
            'meta_description' => fake()->sentence(8),
            'is_visible' => true,
            'is_featured' => false,
        ];
    }
}
