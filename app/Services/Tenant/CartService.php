<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\CartStatus;
use App\Models\Tenant\Cart;
use App\Models\Tenant\CartItem;
use App\Models\Tenant\Customer;
use App\Models\Tenant\FlashSaleProduct;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages shopping cart operations for tenant customers.
 */
class CartService
{
    /**
     * Get or create a cart for a customer.
     *
     * @param Customer $customer
     * @return Cart
     */
    public function getOrCreateForCustomer(Customer $customer): Cart
    {
        return Cart::query()->firstOrCreate(
            ['customer_id' => $customer->id, 'status' => CartStatus::Active],
            ['status' => CartStatus::Active],
        );
    }

    /**
     * Get the active cart for a customer.
     *
     * @param Customer $customer
     * @return Cart|null
     */
    public function getForCustomer(Customer $customer): ?Cart
    {
        return Cart::query()
            ->with(['items.product', 'items.variant'])
            ->where('customer_id', $customer->id)
            ->where('status', CartStatus::Active)
            ->first();
    }

    /**
     * Add an item to a customer's cart.
     *
     * @param Customer $customer
     * @param array<string, mixed> $data
     * @return Cart
     * @throws Throwable
     */
    public function addItem(Customer $customer, array $data): Cart
    {
        return DB::transaction(function () use ($customer, $data): Cart {
            $cart = $this->getOrCreateForCustomer($customer);
            $product = Product::query()->findOrFail($data['product_id']);
            $variantId = $data['product_variant_id'] ?? null;
            $quantity = (int)($data['quantity'] ?? 1);
            $unitPrice = $this->resolveUnitPrice($product, $variantId, $data['flash_sale_id'] ?? null);

            $item = $cart->items()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variantId)
                ->first();

            if ($item !== null) {
                $item->update([
                    'quantity' => $item->quantity + $quantity,
                    'unit_price' => $unitPrice,
                ]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            return $cart->load(['items.product', 'items.variant']);
        });
    }

    /**
     * Update the quantity of a cart item.
     *
     * @param CartItem $item
     * @param int $quantity
     * @return Cart
     */
    public function updateItemQuantity(CartItem $item, int $quantity): Cart
    {
        if ($quantity <= 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $quantity]);
        }

        return $item->cart->load(['items.product', 'items.variant']);
    }

    /**
     * Remove an item from the cart.
     *
     * @param CartItem $item
     * @return Cart
     */
    public function removeItem(CartItem $item): Cart
    {
        $cart = $item->cart;
        $item->delete();

        return $cart->load(['items.product', 'items.variant']);
    }

    /**
     * Clear all items from a cart.
     *
     * @param Cart $cart
     * @return void
     */
    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * Mark a cart as converted.
     *
     * @param Cart $cart
     * @return void
     */
    public function markConverted(Cart $cart): void
    {
        $cart->update(['status' => CartStatus::Converted]);
    }

    /**
     * Resolve the unit price for a product.
     *
     * @param Product $product
     * @param int|null $variantId
     * @param int|null $flashSaleId
     * @return float
     */
    private function resolveUnitPrice(Product $product, ?int $variantId, ?int $flashSaleId): float
    {
        if ($flashSaleId !== null) {
            $flashSaleProduct = FlashSaleProduct::query()
                ->where('flash_sale_id', $flashSaleId)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variantId)
                ->first();

            if ($flashSaleProduct !== null) {
                return (float)$flashSaleProduct->sale_price;
            }
        }

        if ($variantId !== null) {
            $variant = ProductVariant::query()->find($variantId);
            if ($variant !== null) {
                return (float)$variant->price;
            }
        }

        return (float)$product->price;
    }
}
