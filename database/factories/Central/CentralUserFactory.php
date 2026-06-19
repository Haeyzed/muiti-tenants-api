<?php

declare(strict_types=1);

namespace Database\Factories\Central;

use App\Models\Central\CentralUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<CentralUser>
 */
class CentralUserFactory extends Factory
{
    protected $model = CentralUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
