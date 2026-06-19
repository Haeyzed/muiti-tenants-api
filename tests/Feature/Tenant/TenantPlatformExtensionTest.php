<?php

declare(strict_types=1);

use App\Models\Tenant\TeamInvitation;
use App\Services\Tenant\WorldService;
use Illuminate\Support\Collection;

it('returns onboarding progress for store owner', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/onboarding")
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['progress', 'steps']]);
});

it('updates business settings during onboarding', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/settings/business", [
            'business_name' => 'Acme Retail',
            'business_email' => 'hello@acme.test',
            'country_code' => 'US',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.business_name', 'Acme Retail');
});

it('returns world country options with value and label', function (): void {
    $ctx = initializeTenantForTest();

    $this->mock(WorldService::class, function ($mock): void {
        $mock->shouldReceive('countryOptions')
            ->once()
            ->andReturn(collect([
                ['value' => 'US', 'label' => 'United States'],
            ]));
    });

    $response = $this->getJson("http://{$ctx->domain}/api/v1/tenant/world/countries");

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $first = $response->json('data.0');

    expect($first)->toHaveKeys(['value', 'label']);
});

it('creates and lists customers', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/customers", [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.test',
        ])
        ->assertCreated()
        ->assertJsonPath('data.first_name', 'Jane');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customers")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

it('denies customer management without permission', function (): void {
    $ctx = initializeTenantForTest(role: 'inventory-manager');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/customers", [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ])
        ->assertForbidden();
});

it('creates staff and clocks attendance', function (): void {
    $ctx = initializeTenantForTest(role: 'hr-manager');

    $response = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/staff", [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john@staff.test',
        ])
        ->assertCreated();

    $staffId = $response->json('data.id');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/hr/staff/{$staffId}/clock-in")
        ->assertCreated()
        ->assertJsonPath('data.staff_id', $staffId);
});

it('calculates tax for an amount', function (): void {
    $ctx = initializeTenantForTest(role: 'finance-manager');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax/calculate", [
            'amount' => 100,
            'country_code' => 'US',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['subtotal', 'tax_total', 'total', 'breakdown']]);
});

it('sends and accepts team invitations', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/team/invitations", [
            'email' => 'invited@team.test',
            'role' => 'store-manager',
        ])
        ->assertCreated();

    tenancy()->initialize($ctx->tenant);

    $token = TeamInvitation::query()->where('email', 'invited@team.test')->value('token');

    tenancy()->end();

    expect($token)->not->toBeEmpty();

    $this->postJson("http://{$ctx->domain}/api/v1/tenant/team/invitations/accept", [
        'token' => $token,
        'name' => 'Invited User',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['user', 'token']]);
});
