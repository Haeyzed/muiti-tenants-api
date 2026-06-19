<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreBrandRequest;
use App\Http\Requests\Tenant\UpdateBrandRequest;
use App\Http\Resources\Tenant\BrandResource;
use App\Models\Tenant\Brand;
use App\Services\Tenant\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

/**
 * Manages product brands within a tenant store API.
 */
class BrandController extends ApiController
{
    public function __construct(
        private readonly BrandService $brandService,
    ) {}

    /**
     * Get a paginated list of brands.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_visible' => ['nullable', 'in:visible,hidden'],
        ]);

        $brands = $this->brandService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($brands, BrandResource::collection($brands), 'Brands retrieved successfully.');
    }

    /**
     * Create a new brand.
     *
     * @param  StoreBrandRequest  $request
     * @return JsonResponse
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $this->authorize('create', Brand::class);

        $brand = $this->brandService->create(
            $request->safe()->except(['logo']),
            $request->file('logo'),
        );

        return $this->created(
            new BrandResource($brand->load('logoMedia')),
            'Brand created successfully.',
        );
    }

    /**
     * Get a single brand.
     *
     * @param  Brand  $brand
     * @return JsonResponse
     */
    public function show(Brand $brand): JsonResponse
    {
        $this->authorize('view', $brand);

        return $this->success(new BrandResource($this->brandService->find($brand->id)), 'Brand retrieved successfully.');
    }

    /**
     * Update an existing brand.
     *
     * @param  UpdateBrandRequest  $request
     * @param  Brand  $brand
     * @return JsonResponse
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        $brand = $this->brandService->update(
            $brand,
            $request->safe()->except(['logo']),
            $request->file('logo'),
        );

        return $this->updated(
            new BrandResource($brand),
            'Brand updated successfully.',
        );
    }

    /**
     * Delete a brand.
     *
     * @param  Brand  $brand
     * @return JsonResponse
     */
    public function destroy(Brand $brand): JsonResponse
    {
        $this->authorize('delete', $brand);

        $this->brandService->delete($brand);

        return $this->deleted('Brand deleted successfully.');
    }

    /**
     * Delete multiple brands.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Brand::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:brands,id'],
        ]);

        $count = $this->brandService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} brands deleted successfully.");
    }

    /**
     * Force delete a brand permanently.
     *
     * @param  Brand  $brand
     * @return JsonResponse
     */
    public function forceDestroy(Brand $brand): JsonResponse
    {
        $this->authorize('forceDelete', $brand);

        $this->brandService->forceDelete($brand);

        return $this->deleted('Brand permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted brand.
     *
     * @param  Brand  $brand
     * @return JsonResponse
     */
    public function restore(Brand $brand): JsonResponse
    {
        $this->authorize('restore', $brand);

        $brand = $this->brandService->restore($brand);

        return $this->success(
            new BrandResource($brand),
            'Brand restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted brands.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Brand::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->brandService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} brands restored successfully.");
    }
}
