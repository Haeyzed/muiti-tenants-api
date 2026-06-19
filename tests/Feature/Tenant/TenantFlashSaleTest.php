<?php

declare(strict_types=1);

use App\Enums\Tenant\CheckoutSessionStatus;
use App\Enums\Tenant\FlashSaleStatus;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product;

it('creates and activates a flash sale', function (): void {
    $ctx = initializeTenantForTest();

    $startsAt = now()->addHour()->toIso8601String();
    $endsAt = now()->addHours(3)->toIso8601String();

    $create = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/flash-sales", [
            'name' => 'Summer Drop',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

    $create->assertCreated()
        ->assertJsonPath('data.status', FlashSaleStatus::Scheduled->value);

    $flashSaleId = $create->json('data.id');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/flash-sales/{$flashSaleId}/activate")
        ->assertSuccessful()
        ->assertJsonPath('data.status', FlashSaleStatus::Active->value);
});

it('attaches products to a flash sale', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create();
    $flashSale = FlashSale::factory()->create();
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/flash-sales/{$flashSale->id}/products", [
            'product_id' => $product->id,
            'sale_price' => 79.99,
            'stock_limit' => 25,
        ])
        ->assertCreated()
        ->assertJsonPath('data.sale_price', '79.99')
        ->assertJsonPath('data.remaining_stock', 25);
});

it('joins checkout queue for a live flash sale', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);
    $flashSale = FlashSale::factory()->active()->create();
    tenancy()->end();

    $response = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/flash-sales/{$flashSale->id}/queue/join");

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['session_token', 'status']]);

    expect($response->json('data.status'))->toBeIn([
        CheckoutSessionStatus::Waiting->value,
        CheckoutSessionStatus::Admitted->value,
    ]);
});

it('returns checkout queue session status', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);
    $flashSale = FlashSale::factory()->active()->create();
    tenancy()->end();

    $join = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/flash-sales/{$flashSale->id}/queue/join");

    $token = $join->json('data.session_token');

    $this->getJson("http://{$ctx->domain}/api/v1/tenant/checkout/queue-status?session_token={$token}")
        ->assertSuccessful()
        ->assertJsonPath('data.session_token', $token);
});

it('joins and leaves a product waitlist', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create();
    tenancy()->end();

    $join = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products/{$product->id}/waitlist/join", [
            'email' => $ctx->user->email,
        ]);

    $join->assertCreated()
        ->assertJsonPath('data.email', $ctx->user->email);

    $subscriberId = $join->json('data.id');

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/waitlist-subscribers/{$subscriberId}")
        ->assertSuccessful();
});
