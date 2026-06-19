<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\TaxConfigurationUpdated;
use App\Models\Tenant\TaxRate;
use App\Models\Tenant\TaxRegion;
use App\Models\Tenant\TaxRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages tax rules linking rates to regions.
 */
class TaxRuleService
{
    /**
     * Paginate the tax rules.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, TaxRule>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TaxRule::query()
            ->with(['taxRate', 'taxRegion'])
            ->latest();

        if (!empty($filters['tax_rate_id'])) {
            $query->where('tax_rate_id', $filters['tax_rate_id']);
        }

        if (!empty($filters['tax_region_id'])) {
            $query->where('tax_region_id', $filters['tax_region_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a tax rule by ID.
     *
     * @param int $id
     * @return TaxRule
     */
    public function find(int $id): TaxRule
    {
        return TaxRule::query()
            ->with(['taxRate.taxClass', 'taxRegion'])
            ->findOrFail($id);
    }

    /**
     * Create a new tax rule.
     *
     * @param TaxRate $taxRate
     * @param array<string, mixed> $data
     * @return TaxRule
     * @throws Throwable
     */
    public function create(TaxRate $taxRate, array $data): TaxRule
    {
        return DB::transaction(function () use ($taxRate, $data): TaxRule {
            $rule = $taxRate->rules()->create($data);
            TaxConfigurationUpdated::dispatch('tax_rule');

            return $rule->load(['taxRate', 'taxRegion']);
        });
    }

    /**
     * Update an existing tax rule.
     *
     * @param TaxRule $rule
     * @param array<string, mixed> $data
     * @return TaxRule
     * @throws Throwable
     */
    public function update(TaxRule $rule, array $data): TaxRule
    {
        return DB::transaction(function () use ($rule, $data): TaxRule {
            $rule->update($data);
            TaxConfigurationUpdated::dispatch('tax_rule');

            return $rule->fresh(['taxRate', 'taxRegion']);
        });
    }

    /**
     * Delete a tax rule.
     *
     * @param TaxRule $rule
     * @return void
     * @throws Throwable
     */
    public function delete(TaxRule $rule): void
    {
        DB::transaction(function () use ($rule): void {
            $rule->delete();
            TaxConfigurationUpdated::dispatch('tax_rule');
        });
    }

    /**
     * Create a new tax region.
     *
     * @param array<string, mixed> $data
     * @return TaxRegion
     * @throws Throwable
     */
    public function createRegion(array $data): TaxRegion
    {
        return DB::transaction(function () use ($data): TaxRegion {
            $region = TaxRegion::query()->create($data);
            TaxConfigurationUpdated::dispatch('tax_region');

            return $region;
        });
    }

    /**
     * Update an existing tax region.
     *
     * @param TaxRegion $region
     * @param array<string, mixed> $data
     * @return TaxRegion
     * @throws Throwable
     */
    public function updateRegion(TaxRegion $region, array $data): TaxRegion
    {
        return DB::transaction(function () use ($region, $data): TaxRegion {
            $region->update($data);
            TaxConfigurationUpdated::dispatch('tax_region');

            return $region->fresh();
        });
    }

    /**
     * Delete a tax region.
     *
     * @param TaxRegion $region
     * @return void
     * @throws Throwable
     */
    public function deleteRegion(TaxRegion $region): void
    {
        DB::transaction(function () use ($region): void {
            $region->delete();
            TaxConfigurationUpdated::dispatch('tax_region');
        });
    }
}
