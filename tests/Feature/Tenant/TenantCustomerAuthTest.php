<?php

declare(strict_types=1);

it('registers and logs in storefront customers', function (): void {
    $ctx = initializeTenantForTest(role: 'store-owner');

    $register = $this->postJson("http://{$ctx->domain}/api/v1/tenant/customer/auth/register", [
        'first_name' => 'Shop',
        'last_name' => 'Buyer',
        'email' => 'buyer@store.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $register->assertCreated()
        ->assertJsonPath('data.customer.email', 'buyer@store.test')
        ->assertJsonStructure(['data' => ['user', 'customer', 'token']]);

    $this->postJson("http://{$ctx->domain}/api/v1/tenant/customer/auth/login", [
        'email' => 'buyer@store.test',
        'password' => 'password123',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.customer.email', 'buyer@store.test');

    $this->postJson("http://{$ctx->domain}/api/v1/tenant/auth/login", [
        'email' => 'buyer@store.test',
        'password' => 'password123',
    ])
        ->assertForbidden();
});

it('rejects customer login without a customer profile', function (): void {
    $ctx = initializeTenantForTest(role: 'store-owner');

    $this->postJson("http://{$ctx->domain}/api/v1/tenant/customer/auth/login", [
        'email' => $ctx->user->email,
        'password' => 'password',
    ])
        ->assertUnprocessable();
});
