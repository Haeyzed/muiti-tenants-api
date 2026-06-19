<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Inventory;
use App\Models\Tenant\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Manages product stock levels within a tenant store.
 */
class InventoryService
{
    /**
     * Upsert inventory for a product.
     *
     * @param Product $product
     * @param array<string, mixed> $data
     * @param int|null $variantId
     * @return Inventory
     */
    public function upsertForProduct(Product $product, array $data, ?int $variantId = null): Inventory
    {
        return Inventory::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
            ],
            [
                'quantity' => $data['quantity'] ?? 0,
                'reserved_quantity' => $data['reserved_quantity'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
            ],
        );
    }

    /**
     * Adjust inventory quantity.
     *
     * @param Inventory $inventory
     * @param int $quantityDelta
     * @return Inventory
     * @throws Throwable
     */
    public function adjust(Inventory $inventory, int $quantityDelta): Inventory
    {
        return DB::transaction(function () use ($inventory, $quantityDelta): Inventory {
            $inventory->refresh();
            $inventory->update([
                'quantity' => max(0, $inventory->quantity + $quantityDelta),
            ]);

            return $inventory->fresh();
        });
    }

    /**
     * Reserve inventory quantity.
     *
     * @param Inventory $inventory
     * @param int $quantity
     * @return Inventory
     * @throws RuntimeException|Throwable
     */
    public function reserve(Inventory $inventory, int $quantity): Inventory
    {
        return DB::transaction(function () use ($inventory, $quantity): Inventory {
            $inventory->refresh();

            if ($inventory->availableQuantity() < $quantity) {
                throw new RuntimeException('Insufficient available inventory.');
            }

            $inventory->update([
                'reserved_quantity' => $inventory->reserved_quantity + $quantity,
            ]);

            return $inventory->fresh();
        });
    }

    /**
     * Release reserved inventory quantity.
     *
     * @param Inventory $inventory
     * @param int $quantity
     * @return Inventory
     */
    public function release(Inventory $inventory, int $quantity): Inventory
    {
        $inventory->update([
            'reserved_quantity' => max(0, $inventory->reserved_quantity - $quantity),
        ]);

        return $inventory->fresh();
    }
}
