<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\CheckoutSessionStatus;
use App\Enums\Tenant\OrderStatus;
use App\Events\Tenant\OrderPlaced;
use App\Events\Tenant\OrderStatusUpdated;
use App\Models\Tenant\CheckoutSession;
use App\Models\Tenant\Customer;
use App\Models\Tenant\FlashSaleProduct;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Manages order placement and lifecycle within a tenant store.
 */
class OrderService
{
    public function __construct(
        private readonly CartService            $cartService,
        private readonly InventoryService       $inventoryService,
        private readonly CheckoutSessionService $checkoutSessionService,
    )
    {
    }

    /**
     * Paginate the orders.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['customer', 'items', 'payment'])
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find an order by ID.
     *
     * @param int $id
     * @return Order
     */
    public function find(int $id): Order
    {
        return Order::query()
            ->with(['customer.user', 'items', 'addresses', 'statusHistory', 'payment', 'flashSale'])
            ->findOrFail($id);
    }

    /**
     * Place an order from the customer's cart.
     *
     * @param Customer $customer
     * @param array<string, mixed> $data
     * @return Order
     * @throws RuntimeException|Throwable
     */
    public function placeFromCart(Customer $customer, array $data): Order
    {
        return DB::transaction(function () use ($customer, $data): Order {
            $cart = $this->cartService->getForCustomer($customer);

            if ($cart === null || $cart->items->isEmpty()) {
                throw new RuntimeException('Cart is empty.');
            }

            $flashSaleId = $data['flash_sale_id'] ?? null;

            if ($flashSaleId !== null) {
                $this->validateCheckoutSession($data['checkout_session_token'] ?? null, (int)$flashSaleId);
            }

            $subtotal = $cart->subtotal();
            $shipping = (float)($data['shipping_total'] ?? 0);
            $tax = (float)($data['tax_total'] ?? 0);
            $discount = (float)($data['discount_total'] ?? 0);
            $grandTotal = $subtotal - $discount + $tax + $shipping;

            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::Pending,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'shipping_total' => $shipping,
                'grand_total' => $grandTotal,
                'currency' => $data['currency'] ?? 'USD',
                'flash_sale_id' => $flashSaleId,
            ]);

            foreach ($cart->items as $item) {
                $this->fulfillInventory($item->product_id, $item->product_variant_id, $item->quantity, $flashSaleId);

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->variant?->sku ?? $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->lineTotal(),
                ]);
            }

            foreach ($data['addresses'] ?? [] as $address) {
                $order->addresses()->create($address);
            }

            $this->recordStatus(
                $order,
                OrderStatus::Pending,
                'Order placed.',
                $customer->user_id,
            );

            $customer->increment('orders_count');
            $customer->increment('total_spent', $grandTotal);

            $this->cartService->clear($cart);
            $this->cartService->markConverted($cart);

            if (!empty($data['checkout_session_token'])) {
                $session = CheckoutSession::query()
                    ->where('session_token', $data['checkout_session_token'])
                    ->first();

                if ($session !== null) {
                    $this->checkoutSessionService->complete($session);
                }
            }

            $order = $this->find($order->id);
            OrderPlaced::dispatch($order);

            return $order;
        });
    }

    /**
     * Update the status of an order.
     *
     * @param Order $order
     * @param OrderStatus $status
     * @param string|null $notes
     * @param int|null $changedBy
     * @return Order
     */
    public function updateStatus(Order $order, OrderStatus $status, ?string $notes = null, ?int $changedBy = null): Order
    {
        $order->update(['status' => $status]);
        $this->recordStatus($order, $status, $notes, $changedBy);

        $order = $order->fresh();
        OrderStatusUpdated::dispatch($order);

        return $order;
    }

    /**
     * Cancel an order.
     *
     * @param Order $order
     * @param int|null $changedBy
     * @return Order
     */
    public function cancel(Order $order, ?int $changedBy = null): Order
    {
        return $this->updateStatus($order, OrderStatus::Cancelled, 'Order cancelled.', $changedBy);
    }

    /**
     * Record a status change in the order history.
     *
     * @param Order $order
     * @param OrderStatus $status
     * @param string|null $notes
     * @param int|null $changedBy
     * @return void
     */
    private function recordStatus(Order $order, OrderStatus $status, ?string $notes, ?int $changedBy): void
    {
        $order->statusHistory()->create([
            'status' => $status->value,
            'notes' => $notes,
            'changed_by' => $changedBy,
        ]);
    }

    /**
     * Generate a unique order number.
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(Str::random(10));
    }

    /**
     * Validate a checkout session token for a flash sale.
     *
     * @param string|null $token
     * @param int $flashSaleId
     * @return void
     * @throws RuntimeException
     */
    private function validateCheckoutSession(?string $token, int $flashSaleId): void
    {
        if ($token === null) {
            throw new RuntimeException('Checkout session token is required for flash sale orders.');
        }

        $session = CheckoutSession::query()
            ->where('session_token', $token)
            ->whereHas('queue', fn($q) => $q->where('flash_sale_id', $flashSaleId))
            ->first();

        if ($session === null || $session->status !== CheckoutSessionStatus::Admitted) {
            throw new RuntimeException('Valid admitted checkout session is required.');
        }

        if ($session->expires_at !== null && $session->expires_at->isPast()) {
            throw new RuntimeException('Checkout session has expired.');
        }
    }

    /**
     * Fulfill inventory for an order item.
     *
     * @param int $productId
     * @param int|null $variantId
     * @param int $quantity
     * @param int|null $flashSaleId
     * @return void
     * @throws RuntimeException|Throwable
     */
    private function fulfillInventory(int $productId, ?int $variantId, int $quantity, ?int $flashSaleId): void
    {
        $inventory = Inventory::query()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($inventory !== null) {
            $this->inventoryService->adjust($inventory, -$quantity);
        }

        if ($flashSaleId !== null) {
            $flashSaleProduct = FlashSaleProduct::query()
                ->where('flash_sale_id', $flashSaleId)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->first();

            if ($flashSaleProduct !== null) {
                if ($flashSaleProduct->stock_limit !== null
                    && ($flashSaleProduct->sold_count + $quantity) > $flashSaleProduct->stock_limit) {
                    throw new RuntimeException('Flash sale stock limit exceeded.');
                }

                $flashSaleProduct->increment('sold_count', $quantity);
            }
        }
    }
}
