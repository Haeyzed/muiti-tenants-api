<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\FlashSaleStatus;
use App\Events\Tenant\FlashSaleActivated;
use App\Events\Tenant\FlashSaleEnded;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\FlashSaleProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages flash sale lifecycle and product drops.
 */
class FlashSaleService
{
    public function __construct(
        private readonly DropSchedulerService $schedulerService,
    )
    {
    }

    /**
     * Paginate the flash sales.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, FlashSale>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = FlashSale::query()
            ->withCount('products')
            ->latest('starts_at');

        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $query->where('name', 'like', "%$search%");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a flash sale by ID.
     *
     * @param int $id
     * @return FlashSale
     */
    public function find(int $id): FlashSale
    {
        return FlashSale::query()
            ->with(['products.product', 'products.variant', 'rules', 'checkoutQueue'])
            ->findOrFail($id);
    }

    /**
     * Create a new flash sale.
     *
     * @param array<string, mixed> $data
     * @return FlashSale
     * @throws Throwable
     */
    public function create(array $data): FlashSale
    {
        return DB::transaction(function () use ($data): FlashSale {
            $rules = $data['rules'] ?? [];
            unset($data['rules']);

            /** @var FlashSale $flashSale */
            $flashSale = FlashSale::query()->create([
                ...$data,
                'status' => FlashSaleStatus::Scheduled,
                'is_active' => false,
            ]);

            foreach ($rules as $rule) {
                $flashSale->rules()->create($rule);
            }

            return $this->find($flashSale->id);
        });
    }

    /**
     * Update an existing flash sale.
     *
     * @param FlashSale $flashSale
     * @param array<string, mixed> $data
     * @return FlashSale
     */
    public function update(FlashSale $flashSale, array $data): FlashSale
    {
        $flashSale->update($data);

        return $this->find($flashSale->id);
    }

    /**
     * Delete a flash sale.
     *
     * @param FlashSale $flashSale
     * @return void
     */
    public function delete(FlashSale $flashSale): void
    {
        $flashSale->delete();
    }

    /**
     * Delete multiple flash sales by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return FlashSale::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a flash sale permanently.
     *
     * @param FlashSale $flashSale
     * @return void
     */
    public function forceDelete(FlashSale $flashSale): void
    {
        $flashSale->forceDelete();
    }

    /**
     * Restore a soft-deleted flash sale.
     *
     * @param FlashSale $flashSale
     * @return FlashSale
     */
    public function restore(FlashSale $flashSale): FlashSale
    {
        $flashSale->restore();

        return $flashSale->fresh();
    }

    /**
     * Restore multiple soft-deleted flash sales by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return FlashSale::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Attach a product to a flash sale.
     *
     * @param FlashSale $flashSale
     * @param array<string, mixed> $data
     * @return FlashSaleProduct
     */
    public function attachProduct(FlashSale $flashSale, array $data): FlashSaleProduct
    {
        return $flashSale->products()->create($data);
    }

    /**
     * Detach a product from a flash sale.
     *
     * @param FlashSale $flashSale
     * @param FlashSaleProduct $flashSaleProduct
     * @return void
     */
    public function detachProduct(FlashSale $flashSale, FlashSaleProduct $flashSaleProduct): void
    {
        abort_unless($flashSaleProduct->flash_sale_id === $flashSale->id, 404);

        $flashSaleProduct->delete();
    }

    /**
     * Force detach a product from a flash sale permanently.
     *
     * @param FlashSale $flashSale
     * @param FlashSaleProduct $flashSaleProduct
     * @return void
     */
    public function forceDetachProduct(FlashSale $flashSale, FlashSaleProduct $flashSaleProduct): void
    {
        abort_unless($flashSaleProduct->flash_sale_id === $flashSale->id, 404);

        $flashSaleProduct->forceDelete();
    }

    /**
     * Activate a flash sale.
     *
     * @param FlashSale $flashSale
     * @return FlashSale
     */
    public function activate(FlashSale $flashSale): FlashSale
    {
        $flashSale = $this->schedulerService->activate($flashSale);
        FlashSaleActivated::dispatch($flashSale);

        return $flashSale;
    }

    /**
     * End a flash sale.
     *
     * @param FlashSale $flashSale
     * @return FlashSale
     */
    public function end(FlashSale $flashSale): FlashSale
    {
        $flashSale = $this->schedulerService->end($flashSale);
        FlashSaleEnded::dispatch($flashSale);

        return $flashSale;
    }
}
