<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Models\Central\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Manages platform subscription plans.
 */
class PlanService
{
    /**
     * Paginate subscription plans.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Plan>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Plan::query()->orderBy('sort_order');

        if (! empty($filters['is_active'])) {
            $statuses = (array) $filters['is_active'];
            $activeSelected = in_array('active', $statuses, true);
            $inactiveSelected = in_array('inactive', $statuses, true);

            if ($activeSelected && ! $inactiveSelected) {
                $query->where('is_active', true);
            } elseif ($inactiveSelected && ! $activeSelected) {
                $query->where('is_active', false);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get active subscription plans.
     *
     * @return Collection<int, Plan>
     */
    public function activePlans(): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Find a plan by its slug.
     *
     * @param string $slug
     * @return Plan
     */
    public function findBySlug(string $slug): Plan
    {
        return Plan::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Find a plan by its ID.
     *
     * @param int $id
     * @return Plan
     */
    public function find(int $id): Plan
    {
        return Plan::query()->findOrFail($id);
    }

    /**
     * Create a new subscription plan.
     *
     * @param array<string, mixed> $data
     * @return Plan
     */
    public function create(array $data): Plan
    {
        return Plan::query()->create($data);
    }

    /**
     * Update an existing subscription plan.
     *
     * @param Plan $plan
     * @param array<string, mixed> $data
     * @return Plan
     */
    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);

        return $plan->fresh();
    }

    /**
     * Delete a subscription plan.
     *
     * @param Plan $plan
     * @return void
     * @throws RuntimeException
     */
    public function delete(Plan $plan): void
    {
        if ($plan->slug === config('billing.default_plan')) {
            throw new RuntimeException('The default plan cannot be deleted.');
        }

        $plan->delete();
    }

    /**
     * @param list<int> $ids
     */
    public function deleteMany(array $ids): int
    {
        $defaultPlan = (string) config('billing.default_plan');

        return Plan::query()
            ->whereIn('id', $ids)
            ->where('slug', '!=', $defaultPlan)
            ->delete();
    }

    /**
     * @param list<int>|null $ids
     * @return Collection<int, Plan>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Plan::query()->orderBy('sort_order');

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get plan statistics.
     *
     * @return array{total: int, active: int, inactive: int, featured: int}
     */
    public function statistics(): array
    {
        return [
            'total' => Plan::query()->count(),
            'active' => Plan::query()->where('is_active', true)->count(),
            'inactive' => Plan::query()->where('is_active', false)->count(),
            'featured' => Plan::query()->where('is_featured', true)->count(),
        ];
    }

    /**
     * Get plan options.
     *
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (Plan $plan) => [
                'label' => $plan->name,
                'value' => $plan->slug,
            ]);
    }
}
