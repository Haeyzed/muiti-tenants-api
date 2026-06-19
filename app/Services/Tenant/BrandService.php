<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

/**
 * Manages product brands within a tenant store.
 */
class BrandService
{
    /**
     * Paginate the brands.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Brand::query()
            ->latest()
            ->filter($filters)
            ->paginate($perPage);
    }

    /**
     * Find a brand by ID.
     *
     * @param int $id
     * @return Brand
     */
    public function find(int $id): Brand
    {
        return Brand::query()->findOrFail($id);
    }

    /**
     * Create a new brand.
     *
     * @param array<string, mixed> $data
     * @param UploadedFile|null $logo
     * @return Brand
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function create(array $data, ?UploadedFile $logo = null): Brand
    {
        $brand = Brand::query()->create($data);

        if ($logo !== null) {
            $brand->addMedia($logo)->toMediaCollection('logo');
        }

        return $brand->fresh();
    }

    /**
     * Update a brand.
     *
     * @param Brand $brand
     * @param array<string, mixed> $data
     * @param UploadedFile|null $logo
     * @return Brand
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function update(Brand $brand, array $data, ?UploadedFile $logo = null): Brand
    {
        $brand->update($data);

        if ($logo !== null) {
            $brand->clearMediaCollection('logo');
            $brand->addMedia($logo)->toMediaCollection('logo');
        }

        return $brand->fresh();
    }

    /**
     * Delete a brand.
     *
     * @param Brand $brand
     * @return void
     */
    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    /**
     * Delete multiple brands by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Brand::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a brand permanently.
     *
     * @param Brand $brand
     * @return void
     */
    public function forceDelete(Brand $brand): void
    {
        $brand->forceDelete();
    }

    /**
     * Restore a soft-deleted brand.
     *
     * @param Brand $brand
     * @return Brand
     */
    public function restore(Brand $brand): Brand
    {
        $brand->restore();

        return $brand->fresh();
    }

    /**
     * Restore multiple soft-deleted brands by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Brand::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
