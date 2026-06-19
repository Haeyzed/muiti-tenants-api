<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreCategoryRequest;
use App\Http\Requests\Tenant\UpdateCategoryRequest;
use App\Http\Resources\Tenant\CategoryResource;
use App\Models\Tenant\Category;
use App\Services\Tenant\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

/**
 * Manages product categories within a tenant store API.
 */
class CategoryController extends ApiController
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    /**
     * Get a paginated list of categories.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_visible' => ['nullable', 'in:visible,hidden'],
        ]);

        $categories = $this->categoryService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($categories, CategoryResource::collection($categories), 'Categories retrieved successfully.');
    }

    /**
     * Create a new category.
     *
     * @param StoreCategoryRequest $request
     * @return JsonResponse
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->categoryService->create(
            $request->safe()->except(['image']),
            $request->file('image'),
        );

        return $this->created(
            new CategoryResource($category->load('logoMedia')), // Assuming you meant imageMedia if 'image' is the collection name, leaving as is based on your provided file
            'Category created successfully.',
        );
    }

    /**
     * Get a single category.
     *
     * @param  Category  $category
     * @return JsonResponse
     */
    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return $this->success(new CategoryResource($this->categoryService->find($category->id)), 'Category retrieved successfully.');
    }

    /**
     * Update an existing category.
     *
     * @param UpdateCategoryRequest $request
     * @param Category $category
     * @return JsonResponse
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category = $this->categoryService->update(
            $category,
            $request->safe()->except(['image']),
            $request->file('image'),
        );
        return $this->updated(
            new CategoryResource($category),
            'Category updated successfully.',
        );
    }

    /**
     * Delete a category.
     *
     * @param  Category  $category
     * @return JsonResponse
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->categoryService->delete($category);

        return $this->deleted('Category deleted successfully.');
    }

    /**
     * Delete multiple categories.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Category::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $count = $this->categoryService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} categories deleted successfully.");
    }

    /**
     * Force delete a category permanently.
     *
     * @param  Category  $category
     * @return JsonResponse
     */
    public function forceDestroy(Category $category): JsonResponse
    {
        $this->authorize('forceDelete', $category);

        $this->categoryService->forceDelete($category);

        return $this->deleted('Category permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted category.
     *
     * @param  Category  $category
     * @return JsonResponse
     */
    public function restore(Category $category): JsonResponse
    {
        $this->authorize('restore', $category);

        $category = $this->categoryService->restore($category);

        return $this->success(
            new CategoryResource($category),
            'Category restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted categories.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Category::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->categoryService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} categories restored successfully.");
    }
}
