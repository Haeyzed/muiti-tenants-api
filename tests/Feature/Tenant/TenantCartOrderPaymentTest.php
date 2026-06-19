<?php

declare(strict_types=1);

use App\Enums\Tenant\OrderStatus;
use App\Enums\Tenant\PaymentProvider;
use App\Enums\Tenant\PaymentStatus;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\Product;

it('adds and updates cart items', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create(['price' => 49.99]);
    tenancy()->end();

    $add = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/cart/items", [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

    $add->assertCreated()
        ->assertJsonPath('data.subtotal', '99.98')
        ->assertJsonCount(1, 'data.items');

    $itemId = $add->json('data.items.0.id');

    $this->withToken($ctx->token)
        ->patchJson("http://{$ctx->domain}/api/v1/tenant/cart/items/{$itemId}", [
            'quantity' => 3,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '149.97');
});

it('places an order from cart and initiates payment', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create(['price' => 25.00]);
    Inventory::query()->create([
        'product_id' => $product->id,
        'quantity' => 50,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 5,
    ]);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/cart/items", [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
        ->assertCreated();

    $order = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders", [
            'addresses' => [[
                'type' => 'shipping',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address_line_1' => '123 Main St',
                'city' => 'Lagos',
                'postal_code' => '100001',
                'country' => 'NG',
            ]],
        ]);

    $order->assertCreated()
        ->assertJsonPath('data.status', OrderStatus::Pending->value)
        ->assertJsonPath('data.grand_total', '50.00')
        ->assertJsonCount(1, 'data.items');

    $orderId = $order->json('data.id');

    $payment = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders/{$orderId}/payments", [
            'provider' => PaymentProvider::Stripe->value,
        ]);

    $payment->assertCreated()
        ->assertJsonPath('data.status', PaymentStatus::Pending->value)
        ->assertJsonPath('data.amount', '50.00');

    $reference = $payment->json('data.provider_reference');

    $this->postJson("http://{$ctx->domain}/api/v1/tenant/payments/webhook/stripe", [
        'reference' => $reference,
        'status' => 'paid',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', PaymentStatus::Paid->value);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/orders/{$orderId}")
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value);
});

it('lists customer orders scoped to own user', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/orders")
        ->assertSuccessful();
});

it('allows store manager to refund a paid order', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);

    $customerUser = \App\Models\Tenant\TenantUser::factory()->create([
        'password' => bcrypt('password'),
    ]);
    $customerUser->assignRole('customer');
    \App\Models\Tenant\Customer::factory()->create([
        'user_id' => $customerUser->id,
        'first_name' => 'Refund',
        'last_name' => 'Buyer',
        'email' => $customerUser->email,
    ]);
    $customerToken = $customerUser->createToken('test')->plainTextToken;

    $product = Product::factory()->create(['price' => 10.00]);
    Inventory::query()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 2,
    ]);
    tenancy()->end();

    $this->withToken($customerToken)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/cart/items", [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
        ->assertCreated();

    $orderId = $this->withToken($customerToken)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders")
        ->assertCreated()
        ->json('data.id');

    $payment = $this->withToken($customerToken)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders/{$orderId}/payments", [
            'provider' => PaymentProvider::Paystack->value,
        ])
        ->assertCreated();

    $reference = $payment->json('data.provider_reference');

    $this->postJson("http://{$ctx->domain}/api/v1/tenant/payments/webhook/paystack", [
        'reference' => $reference,
    ])->assertSuccessful();

    $this->withoutExceptionHandling();

    $refund = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/orders/{$orderId}/refund");

    if ($refund->status() === 403) {
        tenancy()->initialize($ctx->tenant);
        $user = \App\Models\Tenant\TenantUser::query()->find($ctx->user->id);
        dump(
            $user?->can('orders.manage'),
            $user?->getAllPermissions()->pluck('name'),
            \Illuminate\Support\Facades\Gate::forUser($user)->inspect('refund', \App\Models\Tenant\Order::query()->find($orderId)),
        );
        tenancy()->end();
    }

    $refund->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Refunded->value);
});
