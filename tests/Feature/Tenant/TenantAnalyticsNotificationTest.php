<?php

declare(strict_types=1);

use App\Enums\Tenant\PaymentProvider;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\Product;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Tenant\OrderPlacedNotification;

it('records analytics when placing and paying for an order', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create(['price' => 30.00]);
    Inventory::query()->create([
        'product_id' => $product->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 3,
    ]);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/cart/items", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

    $orderId = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders")
        ->json('data.id');

    $payment = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders/{$orderId}/payments", [
            'provider' => PaymentProvider::Stripe->value,
        ]);

    $reference = $payment->json('data.provider_reference');

    $this->postJson("http://{$ctx->domain}/api/v1/tenant/payments/webhook/stripe", [
        'reference' => $reference,
    ])->assertSuccessful();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/orders/{$orderId}")
        ->assertSuccessful()
        ->assertJsonPath('data.status', \App\Enums\Tenant\OrderStatus::Confirmed->value);
});

it('sends order placed notification to customer', function (): void {
    Notification::fake();

    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create(['price' => 15.00]);
    Inventory::query()->create([
        'product_id' => $product->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 3,
    ]);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/cart/items", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders")
        ->assertCreated();

    Notification::assertSentTo($ctx->user, OrderPlacedNotification::class);
});

it('lists user notifications', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create(['price' => 12.00]);
    Inventory::query()->create([
        'product_id' => $product->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 3,
    ]);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/cart/items", ['product_id' => $product->id, 'quantity' => 1]);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders")
        ->assertCreated();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/notifications")
        ->assertSuccessful();
});
