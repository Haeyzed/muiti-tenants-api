<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

/**
 * Manages product categories within a tenant store.
 */
class CategoryService
{
    /**
     * Paginate the categories.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with('parent')
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a category by ID.
     *
     * @param int $id
     * @return Category
     */
    public function find(int $id): Category
    {
        return Category::query()
            ->with(['parent', 'children'])
            ->findOrFail($id);
    }

    /**
     * Create a new category.
     *
     * @param array<string, mixed> $data
     * @param UploadedFile|null $image
     * @return Category
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function create(array $data, ?UploadedFile $image = null): Category
    {
        $category = Category::query()->create($data);

        if ($image !== null) {
            $category->addMedia($image)->toMediaCollection('image');
        }

        return $category->fresh();
    }

    /**
     * Update a category.
     *
     * @param Category $category
     * @param array<string, mixed> $data
     * @param UploadedFile|null $image
     * @return Category
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function update(Category $category, array $data, ?UploadedFile $image = null): Category
    {
        $category->update($data);

        if ($image !== null) {
            $category->clearMediaCollection('image');
            $category->addMedia($image)->toMediaCollection('image');
        }

        return $category->fresh();
    }

    /**
     * Delete a category.
     *
     * @param Category $category
     * @return void
     */
    public function delete(Category $category): void
    {
        $category->delete();
    }

    /**
     * Delete multiple categories by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Category::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a category permanently.
     *
     * @param Category $category
     * @return void
     */
    public function forceDelete(Category $category): void
    {
        $category->forceDelete();
    }

    /**
     * Restore a soft-deleted category.
     *
     * @param Category $category
     * @return Category
     */
    public function restore(Category $category): Category
    {
        $category->restore();

        return $category->fresh();
    }

    /**
     * Restore multiple soft-deleted categories by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Category::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
