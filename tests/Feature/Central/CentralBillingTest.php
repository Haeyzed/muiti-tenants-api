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
    $this->admin->assignRole('billing-manager');
    Sanctum::actingAs($this->admin);
});

it('subscribes a tenant in stub billing mode with stripe', function (): void {
    $tenant = \App\Models\Central\Tenant::factory()->create();

    $response = $this->postJson("/api/v1/central/tenants/{$tenant->id}/subscribe", [
        'plan' => 'pro',
        'provider' => 'stripe',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.mode', 'stub')
        ->assertJsonPath('data.plan', 'pro');

    expect($tenant->fresh()->plan)->toBe('pro')
        ->and($tenant->fresh()->billing_provider)->toBe('stripe');
});

it('subscribes a tenant in stub billing mode with paddle', function (): void {
    $tenant = \App\Models\Central\Tenant::factory()->create();

    $response = $this->postJson("/api/v1/central/tenants/{$tenant->id}/subscribe", [
        'plan' => 'starter',
        'provider' => 'paddle',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.mode', 'stub')
        ->assertJsonPath('data.plan', 'starter');

    expect($tenant->fresh()->billing_provider)->toBe('paddle');
});

it('subscribes a tenant in stub billing mode with paystack', function (): void {
    $tenant = \App\Models\Central\Tenant::factory()->create();

    $response = $this->postJson("/api/v1/central/tenants/{$tenant->id}/subscribe", [
        'plan' => 'pro',
        'provider' => 'paystack',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.mode', 'stub')
        ->assertJsonPath('data.plan', 'pro');

    expect($tenant->fresh()->billing_provider)->toBe('paystack');
});

it('subscribes a tenant in stub billing mode with paypal', function (): void {
    $tenant = \App\Models\Central\Tenant::factory()->create();

    $response = $this->postJson("/api/v1/central/tenants/{$tenant->id}/subscribe", [
        'plan' => 'starter',
        'provider' => 'paypal',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.mode', 'stub')
        ->assertJsonPath('data.plan', 'starter');

    expect($tenant->fresh()->billing_provider)->toBe('paypal');
});

it('subscribes a tenant in stub billing mode with flutterwave', function (): void {
    $tenant = \App\Models\Central\Tenant::factory()->create();

    $response = $this->postJson("/api/v1/central/tenants/{$tenant->id}/subscribe", [
        'plan' => 'enterprise',
        'provider' => 'flutterwave',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.mode', 'stub')
        ->assertJsonPath('data.plan', 'enterprise');

    expect($tenant->fresh()->billing_provider)->toBe('flutterwave');
});

it('activates a gateway subscription from webhook payload', function (): void {
    $tenant = \App\Models\Central\Tenant::factory()->create([
        'plan' => 'pro',
        'billing_provider' => 'paystack',
    ]);

    \App\Models\Central\PlatformSubscription::query()->create([
        'tenant_id' => $tenant->id,
        'provider' => 'paystack',
        'plan_slug' => 'pro',
        'status' => 'pending',
    ]);

    $this->postJson('/api/v1/central/billing/webhooks/paystack', [
        'metadata' => [
            'tenant_id' => $tenant->id,
            'plan' => 'pro',
            'provider' => 'paystack',
        ],
        'reference' => 'PSK_TEST_REF',
    ])->assertSuccessful()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.plan', 'pro');
});

it('returns billing plans and subscription summary', function (): void {
    $tenant = \App\Models\Central\Tenant::factory()->create(['plan' => 'starter']);

    $this->getJson('/api/v1/central/billing/plans')
        ->assertSuccessful()
        ->assertJsonStructure(['data' => [['slug', 'name', 'features']]]);

    $this->getJson("/api/v1/central/tenants/{$tenant->id}/subscription")
        ->assertSuccessful()
        ->assertJsonPath('data.plan', 'starter');
});

it('opens billing portal stub when stripe is not configured', function (): void {
    $tenant = \App\Models\Central\Tenant::factory()->create();

    $this->postJson("/api/v1/central/tenants/{$tenant->id}/billing-portal")
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['url']]);
});

it('manages subscription plans', function (): void {
    $this->getJson('/api/v1/central/plans')
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 3);

    $create = $this->postJson('/api/v1/central/plans', [
        'slug' => 'growth',
        'name' => 'Growth',
        'price' => 149.00,
        'features' => ['Advanced analytics'],
    ]);

    $create->assertCreated()
        ->assertJsonPath('data.slug', 'growth');

    $planId = $create->json('data.id');

    $this->putJson("/api/v1/central/plans/{$planId}", [
        'price' => 159.00,
    ])->assertSuccessful()
        ->assertJsonPath('data.price', '159.00');
});
