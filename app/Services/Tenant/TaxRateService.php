<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\TaxConfigurationUpdated;
use App\Models\Tenant\TaxClass;
use App\Models\Tenant\TaxRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages tax rates within tax classes.
 */
class TaxRateService
{
    /**
     * Paginate the tax rates.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, TaxRate>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TaxRate::query()
            ->with('taxClass')
            ->orderBy('priority')
            ->latest();

        if (!empty($filters['tax_class_id'])) {
            $query->where('tax_class_id', $filters['tax_class_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a tax rate by ID.
     *
     * @param int $id
     * @return TaxRate
     */
    public function find(int $id): TaxRate
    {
        return TaxRate::query()
            ->with(['taxClass', 'rules.taxRegion'])
            ->findOrFail($id);
    }

    /**
     * Create a new tax rate.
     *
     * @param TaxClass $taxClass
     * @param array<string, mixed> $data
     * @return TaxRate
     * @throws Throwable
     */
    public function create(TaxClass $taxClass, array $data): TaxRate
    {
        return DB::transaction(function () use ($taxClass, $data): TaxRate {
            $rate = $taxClass->rates()->create($data);
            TaxConfigurationUpdated::dispatch('tax_rate');

            return $rate->load('taxClass');
        });
    }

    /**
     * Update an existing tax rate.
     *
     * @param TaxRate $rate
     * @param array<string, mixed> $data
     * @return TaxRate
     * @throws Throwable
     */
    public function update(TaxRate $rate, array $data): TaxRate
    {
        return DB::transaction(function () use ($rate, $data): TaxRate {
            $rate->update($data);
            TaxConfigurationUpdated::dispatch('tax_rate');

            return $rate->fresh(['taxClass', 'rules.taxRegion']);
        });
    }

    /**
     * Delete a tax rate.
     *
     * @param TaxRate $rate
     * @return void
     * @throws Throwable
     */
    public function delete(TaxRate $rate): void
    {
        DB::transaction(function () use ($rate): void {
            $rate->delete();
            TaxConfigurationUpdated::dispatch('tax_rate');
        });
    }
}
