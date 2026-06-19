<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreProductRequest;
use App\Http\Requests\Tenant\StoreProductVariantRequest;
use App\Http\Requests\Tenant\UpdateProductRequest;
use App\Http\Resources\Tenant\ProductResource;
use App\Http\Resources\Tenant\ProductVariantResource;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Services\Tenant\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Manages products within a tenant store API.
 */
class ProductController extends ApiController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    /**
     * Get a paginated list of products.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search'      => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer'],
            'brand_id'    => ['nullable', 'integer'],
            'is_visible' => ['nullable', 'in:visible,hidden'],
            'is_featured' => ['nullable', 'in:featured,not_featured'],
        ]);

        $products = $this->productService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($products, ProductResource::collection($products), 'Products retrieved successfully.');
    }

    /**
     * Create a new product.
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->productService->create(
            $request->safe()->except(['images']),
            $request->file('images'),
        );

        return $this->created(
            new ProductResource($product),
            'Product created successfully.',
        );
    }

    /**
     * Get a single product.
     *
     * @param  Product  $product
     * @return JsonResponse
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->success(
            new ProductResource($this->productService->find($product->id)),
            'Product retrieved successfully.',
        );
    }

    /**
     * Update an existing product.
     *
     * @param UpdateProductRequest $request
     * @param Product $product
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->update(
            $product,
            $request->safe()->except(['images']),
            $request->file('images'),
        );

        return $this->updated(
            new ProductResource($product),
            'Product updated successfully.',
        );
    }

    /**
     * Delete a product.
     *
     * @param  Product  $product
     * @return JsonResponse
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return $this->deleted('Product deleted successfully.');
    }

    /**
     * Add a variant to a product.
     *
     * @param StoreProductVariantRequest $request
     * @param Product $product
     * @return JsonResponse
     * @throws Throwable
     */
    public function storeVariant(StoreProductVariantRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $variant = $this->productService->createVariant($product, $request->validated());

        return $this->created(
            new ProductVariantResource($variant),
            'Product variant created successfully.',
        );
    }

    /**
     * Remove a variant from a product.
     *
     * @param  Product  $product
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function destroyVariant(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $product);

        abort_unless($variant->product_id === $product->id, 404);

        $this->productService->deleteVariant($variant);

        return $this->deleted('Product variant deleted successfully.');
    }
}
