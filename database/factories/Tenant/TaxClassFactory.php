<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\TaxClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TaxClass>
 */
class TaxClassFactory extends Factory
{
    protected $model = TaxClass::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
