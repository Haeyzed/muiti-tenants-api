<?php

declare(strict_types=1);

use App\Enums\Central\TenantStatus;
use App\Models\Central\CentralUser;
use App\Models\Central\Tenant;
use App\Models\Tenant\TenantUser;
use App\Notifications\Central\TenantOwnerCredentialsNotification;
use Database\Seeders\CentralRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(CentralRolePermissionSeeder::class);

    $this->admin = CentralUser::factory()->create();
    $this->admin->assignRole('super-admin');
    Sanctum::actingAs($this->admin);
});

it('lists tenants with pagination', function (): void {
    Tenant::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/central/tenants');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('creates a tenant with subdomain', function (): void {
    Notification::fake();

    $response = $this->postJson('/api/v1/central/tenants', [
        'name' => 'Flash Store',
        'email' => 'store@example.com',
        'subdomain' => 'flashstore',
        'owner' => [
            'name' => 'Store Owner',
            'email' => 'owner@flashstore.test',
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Flash Store')
        ->assertJsonPath('data.status', TenantStatus::Pending->value);

    $this->assertDatabaseHas('tenants', [
        'name' => 'Flash Store',
        'slug' => 'flash-store',
    ]);

    $this->assertDatabaseHas('domains', [
        'domain' => 'flashstore',
        'is_primary' => true,
    ]);

    $tenant = Tenant::query()->where('slug', 'flash-store')->firstOrFail();

    expect($tenant->fresh()->owner['email'])->toBe('owner@flashstore.test');

    tenancy()->initialize($tenant);

    $ownerUser = TenantUser::query()->where('email', 'owner@flashstore.test')->first();

    expect($ownerUser)->not->toBeNull()
        ->and($ownerUser->hasRole('store-owner'))->toBeTrue();

    tenancy()->end();

    Notification::assertSentOnDemand(
        TenantOwnerCredentialsNotification::class,
        fn (TenantOwnerCredentialsNotification $notification, array $channels, object $notifiable): bool => $notifiable->routeNotificationFor('mail') === 'owner@flashstore.test'
            && $notification->user->email === 'owner@flashstore.test',
    );
});

it('activates and suspends a tenant', function (): void {
    $tenant = Tenant::factory()->create(['status' => TenantStatus::Pending]);

    $this->postJson("/api/v1/central/tenants/{$tenant->id}/activate")
        ->assertSuccessful()
        ->assertJsonPath('data.status', TenantStatus::Active->value);

    $this->postJson("/api/v1/central/tenants/{$tenant->id}/suspend")
        ->assertSuccessful()
        ->assertJsonPath('data.status', TenantStatus::Suspended->value);
});

it('returns tenant statistics', function (): void {
    Tenant::factory()->active()->create();
    Tenant::factory()->suspended()->create();

    $response = $this->getJson('/api/v1/central/tenants/statistics');

    $response->assertSuccessful()
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.active', 1)
        ->assertJsonPath('data.suspended', 1);
});

it('authenticates central admin users', function (): void {
    $user = CentralUser::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('secret-password'),
    ]);
    $user->assignRole('super-admin');

    $response = $this->postJson('/api/v1/central/auth/login', [
        'email' => 'admin@test.com',
        'password' => 'secret-password',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token', 'user']]);
});

it('denies tenant management without permission', function (): void {
    $user = CentralUser::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/central/tenants')->assertForbidden();
});
