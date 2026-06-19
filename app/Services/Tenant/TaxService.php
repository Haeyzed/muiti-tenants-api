<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\TaxType;
use App\Events\Tenant\TaxConfigurationUpdated;
use App\Models\Tenant\TaxClass;
use App\Models\Tenant\TaxRate;
use App\Models\Tenant\TaxRegion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Manages tax classes and calculates tax for amounts by region.
 */
class TaxService
{
    /**
     * Paginate tax classes.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, TaxClass>
     */
    public function paginateClasses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TaxClass::query()->latest();

        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a tax class by ID.
     *
     * @param int $id
     * @return TaxClass
     */
    public function findClass(int $id): TaxClass
    {
        return TaxClass::query()
            ->with(['rates.rules.taxRegion'])
            ->findOrFail($id);
    }

    /**
     * Create a new tax class.
     *
     * @param array<string, mixed> $data
     * @return TaxClass
     */
    public function createClass(array $data): TaxClass
    {
        return DB::transaction(function () use ($data): TaxClass {
            $class = TaxClass::query()->create($data);
            TaxConfigurationUpdated::dispatch('tax_class');

            return $class;
        });
    }

    /**
     * Update an existing tax class.
     *
     * @param TaxClass $taxClass
     * @param array<string, mixed> $data
     * @return TaxClass
     */
    public function updateClass(TaxClass $taxClass, array $data): TaxClass
    {
        return DB::transaction(function () use ($taxClass, $data): TaxClass {
            $taxClass->update($data);
            TaxConfigurationUpdated::dispatch('tax_class');

            return $taxClass->fresh();
        });
    }

    /**
     * Delete a tax class.
     *
     * @param TaxClass $taxClass
     * @return void
     */
    public function deleteClass(TaxClass $taxClass): void
    {
        DB::transaction(function () use ($taxClass): void {
            $taxClass->delete();
            TaxConfigurationUpdated::dispatch('tax_class');
        });
    }

    /**
     * Calculate tax for an amount by region.
     *
     * @param float $amount
     * @param array{country_code: string, state_code?: string|null, postal_code?: string|null} $region
     * @param int|null $taxClassId
     * @return array{subtotal: float, tax_total: float, total: float, breakdown: list<array{rate_id: int, name: string, type: string, rate: string, amount: float}>}
     */
    public function calculate(float $amount, array $region, ?int $taxClassId = null): array
    {
        $taxRegion = $this->resolveRegion($region);

        if ($taxRegion === null) {
            return [
                'subtotal' => $amount,
                'tax_total' => 0.0,
                'total' => $amount,
                'breakdown' => [],
            ];
        }

        $rates = $this->resolveRates($taxRegion, $taxClassId);

        $taxableAmount = $amount;
        $taxTotal = 0.0;
        $breakdown = [];

        foreach ($rates as $rate) {
            $taxAmount = match ($rate->type) {
                TaxType::Percentage => round($taxableAmount * ((float)$rate->rate / 100), 2),
                TaxType::Fixed => round((float)$rate->rate, 2),
            };

            $taxTotal += $taxAmount;

            if ($rate->is_compound) {
                $taxableAmount += $taxAmount;
            }

            $breakdown[] = [
                'rate_id' => $rate->id,
                'name' => $rate->name,
                'type' => $rate->type->value,
                'rate' => (string)$rate->rate,
                'amount' => $taxAmount,
            ];
        }

        return [
            'subtotal' => $amount,
            'tax_total' => round($taxTotal, 2),
            'total' => round($amount + $taxTotal, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Resolve a tax region.
     *
     * @param array{country_code: string, state_code?: string|null, postal_code?: string|null} $region
     * @return TaxRegion|null
     */
    private function resolveRegion(array $region): ?TaxRegion
    {
        $query = TaxRegion::query()
            ->where('is_active', true)
            ->where('country_code', $region['country_code']);

        if (!empty($region['state_code'])) {
            $query->where(function ($builder) use ($region): void {
                $builder->where('state_code', $region['state_code'])
                    ->orWhereNull('state_code');
            });
        }

        $regions = $query->get();

        if ($regions->isEmpty()) {
            return null;
        }

        if (!empty($region['postal_code'])) {
            $matched = $regions->first(function (TaxRegion $taxRegion) use ($region): bool {
                if ($taxRegion->postal_code_pattern === null) {
                    return true;
                }

                return (bool)preg_match('/' . $taxRegion->postal_code_pattern . '/', $region['postal_code']);
            });

            return $matched ?? $regions->first();
        }

        return $regions->first();
    }

    /**
     * Resolve tax rates for a region.
     *
     * @param TaxRegion $taxRegion
     * @param int|null $taxClassId
     * @return Collection<int, TaxRate>
     */
    private function resolveRates(TaxRegion $taxRegion, ?int $taxClassId): Collection
    {
        $query = TaxRate::query()
            ->where('is_active', true)
            ->whereHas('rules', function ($builder) use ($taxRegion): void {
                $builder->where('is_active', true)
                    ->where(function ($ruleQuery) use ($taxRegion): void {
                        $ruleQuery->where('tax_region_id', $taxRegion->id)
                            ->orWhereNull('tax_region_id');
                    });
            })
            ->orderBy('priority');

        if ($taxClassId !== null) {
            $query->where('tax_class_id', $taxClassId);
        }

        return $query->get();
    }
}
