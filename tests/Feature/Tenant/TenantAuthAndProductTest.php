<?php

declare(strict_types=1);

use App\Models\Tenant\Product;

it('authenticates tenant store users', function (): void {
    $ctx = initializeTenantForTest();

    $response = $this->postJson("http://{$ctx->domain}/api/v1/tenant/auth/login", [
        'email' => $ctx->user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token', 'user']]);
});

it('returns authenticated tenant profile', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/auth/me")
        ->assertSuccessful()
        ->assertJsonPath('data.email', $ctx->user->email);
});

it('denies tenant routes without authentication', function (): void {
    $ctx = initializeTenantForTest();

    $this->getJson("http://{$ctx->domain}/api/v1/tenant/products")
        ->assertUnauthorized();
});

it('denies product management without permission', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products", [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 99.99,
        ])
        ->assertForbidden();
});

it('creates and lists products', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products", [
            'name' => 'Flash Sneakers',
            'sku' => 'SNK-001',
            'price' => 149.99,
            'inventory' => ['quantity' => 50],
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Flash Sneakers')
        ->assertJsonPath('data.inventory.quantity', 50);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/products")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

it('creates product variants with inventory', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create();
    $productId = $product->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/variants", [
            'name' => 'Size 42',
            'sku' => 'SNK-001-42',
            'price' => 149.99,
            'is_default' => true,
            'inventory' => ['quantity' => 10],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Size 42')
        ->assertJsonPath('data.inventory.quantity', 10);
});

it('manages categories and brands', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/categories", ['name' => 'Footwear'])
        ->assertCreated();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/brands", ['name' => 'Nike'])
        ->assertCreated();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/brands")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});
