<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\ProductCreated;
use App\Events\Tenant\ProductUpdated;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages products within a tenant flash-sale store.
 */
class ProductService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    )
    {
    }

    /**
     * Paginate the products.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'brand', 'tags', 'inventory'])
            ->latest();

        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['is_visible'])) {
            $query->where('is_visible', (bool)$filters['is_visible']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', (bool)$filters['is_featured']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a product by ID.
     *
     * @param int $id
     * @return Product
     */
    public function find(int $id): Product
    {
        return Product::query()
            ->with(['category', 'brand', 'tags', 'variants.inventory', 'inventory', 'media'])
            ->findOrFail($id);
    }

    /**
     * Create a new product.
     *
     * @param array<string, mixed> $data
     * @param list<UploadedFile>|null $images
     * @return Product
     * @throws Throwable
     */
    public function create(array $data, ?array $images = null): Product
    {
        return DB::transaction(function () use ($data, $images): Product {
            $tagIds = $data['tag_ids'] ?? [];
            $inventoryData = $data['inventory'] ?? [];
            unset($data['tag_ids'], $data['inventory']);

            $product = Product::query()->create($data);

            if ($tagIds !== []) {
                $product->tags()->sync($tagIds);
            }

            $this->inventoryService->upsertForProduct($product, $inventoryData);

            if ($images !== null) {
                foreach ($images as $image) {
                    $product->addMedia($image)->toMediaCollection('images');
                }
            }

            $product = $this->find($product->id);
            ProductCreated::dispatch($product);

            return $product;
        });
    }

    /**
     * Update an existing product.
     *
     * @param Product $product
     * @param array<string, mixed> $data
     * @param list<UploadedFile>|null $images
     * @return Product
     * @throws Throwable
     */
    public function update(Product $product, array $data, ?array $images = null): Product
    {
        return DB::transaction(function () use ($product, $data, $images): Product {
            $tagIds = $data['tag_ids'] ?? null;
            $inventoryData = $data['inventory'] ?? null;
            unset($data['tag_ids'], $data['inventory']);

            $product->update($data);

            if ($tagIds !== null) {
                $product->tags()->sync($tagIds);
            }

            if ($inventoryData !== null) {
                $this->inventoryService->upsertForProduct($product, $inventoryData);
            }

            if ($images !== null) {
                foreach ($images as $image) {
                    $product->addMedia($image)->toMediaCollection('images');
                }
            }

            $product = $this->find($product->id);
            ProductUpdated::dispatch($product);

            return $product;
        });
    }

    /**
     * Delete a product.
     *
     * @param Product $product
     * @return void
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Create a variant for a product.
     *
     * @param Product $product
     * @param array<string, mixed> $data
     * @return ProductVariant
     * @throws Throwable
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data): ProductVariant {
            if (!empty($data['is_default'])) {
                $product->variants()->update(['is_default' => false]);
            }

            $variant = $product->variants()->create($data);

            $this->inventoryService->upsertForProduct(
                $product,
                $data['inventory'] ?? ['quantity' => 0],
                $variant->id,
            );

            return $variant->load('inventory');
        });
    }

    /**
     * Update a product variant.
     *
     * @param ProductVariant $variant
     * @param array<string, mixed> $data
     * @return ProductVariant
     * @throws Throwable
     */
    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data): ProductVariant {
            if (!empty($data['is_default'])) {
                $variant->product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
            }

            $inventoryData = $data['inventory'] ?? null;
            unset($data['inventory']);

            $variant->update($data);

            if ($inventoryData !== null) {
                $this->inventoryService->upsertForProduct($variant->product, $inventoryData, $variant->id);
            }

            return $variant->fresh(['inventory']);
        });
    }

    /**
     * Delete a product variant.
     *
     * @param ProductVariant $variant
     * @return void
     */
    public function deleteVariant(ProductVariant $variant): void
    {
        $variant->delete();
    }
}
