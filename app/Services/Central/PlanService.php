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

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
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
