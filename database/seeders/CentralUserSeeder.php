<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Central\CentralUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a default super admin for the central platform.
 */
class CentralUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = CentralUser::query()->firstOrCreate(
            ['email' => 'admin@multi-tenants-api.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );

        $user->assignRole('super-admin');
    }
}
