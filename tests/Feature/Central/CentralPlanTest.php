<?php

declare(strict_types=1);

use App\Models\Central\CentralUser;
use App\Models\Central\Plan;
use Database\Seeders\CentralRolePermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(CentralRolePermissionSeeder::class);
    $this->seed(PlanSeeder::class);

    $this->admin = CentralUser::factory()->create();
    $this->admin->assignRole('super-admin');
    Sanctum::actingAs($this->admin);
});

it('lists active plans publicly for billing managers', function (): void {
    $this->getJson('/api/v1/central/billing/plans')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('creates updates and deletes a plan', function (): void {
    $plan = Plan::factory()->create(['slug' => 'custom-plan']);

    $this->getJson('/api/v1/central/plans/'.$plan->id)
        ->assertSuccessful()
        ->assertJsonPath('data.slug', 'custom-plan');

    $this->putJson('/api/v1/central/plans/'.$plan->id, [
        'name' => 'Custom Updated',
    ])->assertSuccessful()
        ->assertJsonPath('data.name', 'Custom Updated');

    $this->deleteJson('/api/v1/central/plans/'.$plan->id)
        ->assertSuccessful();

    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});
