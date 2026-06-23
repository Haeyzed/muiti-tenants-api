<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\Tenant\ProductType;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreProductRequest;
use App\Http\Requests\Tenant\StoreProductReviewRequest;
use App\Http\Requests\Tenant\StoreProductVariantRequest;
use App\Http\Requests\Tenant\UpdateProductRequest;
use App\Http\Requests\Tenant\UpdateProductVariantRequest;
use App\Http\Resources\Tenant\ProductResource;
use App\Http\Resources\Tenant\ProductReviewResource;
use App\Http\Resources\Tenant\ProductTypeResource;
use App\Http\Resources\Tenant\ProductVariantResource;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductReview;
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
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'product_type' => ['nullable', 'in:standard,digital,service,combo'],
            'is_visible' => ['nullable', 'in:visible,hidden'],
            'is_featured' => ['nullable', 'in:featured,not_featured'],
            'is_digital' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
        ]);

        $products = $this->productService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($products, ProductResource::collection($products), 'Products retrieved successfully.');
    }

    /**
     * Get featured products for storefront.
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 8);

        $products = $this->productService->getFeaturedProducts($limit);

        return $this->success(
            ProductResource::collection($products),
            'Featured products retrieved successfully.',
        );
    }

    /**
     * Get available product types.
     */
    public function types(): JsonResponse
    {
        return $this->success(
            ProductType::toArray(),
            'Product types retrieved successfully.',
        );
    }

    /**
     * Create a new product.
     *
     * @throws Throwable
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->productService->create($request->validated());

        return $this->created(
            new ProductResource($product),
            'Product created successfully.',
        );
    }

    /**
     * Get a single product.
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
     * Get product by slug for storefront.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $product = $this->productService->findBySlug($slug);
        $product->incrementViews();

        return $this->success(
            new ProductResource($product),
            'Product retrieved successfully.',
        );
    }

    /**
     * Update an existing product.
     *
     * @throws Throwable
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->update($product, $request->validated());

        return $this->updated(
            new ProductResource($product),
            'Product updated successfully.',
        );
    }

    /**
     * Delete a product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return $this->deleted('Product deleted successfully.');
    }

    /**
     * Delete multiple products.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Product::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        $count = $this->productService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} products deleted successfully.");
    }

    /**
     * Force delete a product permanently.
     */
    public function forceDestroy(Product $product): JsonResponse
    {
        $this->authorize('forceDelete', $product);

        $this->productService->forceDelete($product);

        return $this->deleted('Product permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(Product $product): JsonResponse
    {
        $this->authorize('restore', $product);

        $product = $this->productService->restore($product);

        return $this->success(
            new ProductResource($product),
            'Product restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted products.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Product::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->productService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} products restored successfully.");
    }

    // -------------------------------------------------------------------------
    // Variants
    // -------------------------------------------------------------------------

    /**
     * Add a variant to a product.
     *
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
     * Update a product variant.
     *
     * @throws Throwable
     */
    public function updateVariant(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $product);

        abort_unless($variant->product_id === $product->id, 404);

        $variant = $this->productService->updateVariant($variant, $request->validated());

        return $this->updated(
            new ProductVariantResource($variant),
            'Product variant updated successfully.',
        );
    }

    /**
     * Remove a variant from a product.
     */
    public function destroyVariant(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $product);

        abort_unless($variant->product_id === $product->id, 404);

        $this->productService->deleteVariant($variant);

        return $this->deleted('Product variant deleted successfully.');
    }

    // -------------------------------------------------------------------------
    // Reviews
    // -------------------------------------------------------------------------

    /**
     * Get reviews for a product.
     */
    public function reviews(Request $request, Product $product): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);

        $reviews = $product->reviews()
            ->with('replies')
            ->latest()
            ->paginate($perPage);

        return $this->paginated($reviews, ProductReviewResource::collection($reviews), 'Reviews retrieved successfully.');
    }

    /**
     * Add a review to a product.
     */
    public function storeReview(StoreProductReviewRequest $request, Product $product): JsonResponse
    {
        $review = $product->allReviews()->create($request->validated());

        // Recalculate product rating
        $product->recalculateRating();

        return $this->created(
            new ProductReviewResource($review),
            'Review submitted successfully.',
        );
    }

    /**
     * Approve a product review.
     */
    public function approveReview(Product $product, ProductReview $review): JsonResponse
    {
        $this->authorize('manage', Product::class);

        abort_unless($review->product_id === $product->id, 404);

        $review->update(['is_approved' => true]);
        $product->recalculateRating();

        return $this->success(
            new ProductReviewResource($review),
            'Review approved successfully.',
        );
    }

    // -------------------------------------------------------------------------
    // Related Products
    // -------------------------------------------------------------------------

    /**
     * Get related products for a product.
     */
    public function relatedProducts(Request $request, Product $product): JsonResponse
    {
        $limit = $request->integer('limit', 8);

        $related = $this->productService->getRelatedProducts($product, $limit);

        return $this->success(
            ProductResource::collection($related),
            'Related products retrieved successfully.',
        );
    }

    /**
     * Get product structured data for SEO.
     */
    public function structuredData(Product $product): JsonResponse
    {
        return $this->success(
            $product->toStructuredData(),
            'Product structured data retrieved successfully.',
        );
    }
}
