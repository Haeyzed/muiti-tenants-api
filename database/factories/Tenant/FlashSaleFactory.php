<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\FlashSaleStatus;
use App\Models\Tenant\FlashSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlashSale>
 */
class FlashSaleFactory extends Factory
{
    protected $model = FlashSale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay();

        return [
            'name' => fake()->words(3, true).' Drop',
            'description' => fake()->sentence(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(2),
            'status' => FlashSaleStatus::Scheduled,
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => FlashSaleStatus::Active,
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
    }
}
