<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\FlashSaleStatus;
use App\Models\Tenant\FlashSale;
use Illuminate\Support\Collection;

/**
 * Schedules and processes flash sale activation and expiration.
 */
class DropSchedulerService
{
    /**
     * Activate a flash sale.
     *
     * @param FlashSale $flashSale
     * @return FlashSale
     */
    public function activate(FlashSale $flashSale): FlashSale
    {
        $flashSale->update([
            'status' => FlashSaleStatus::Active,
            'is_active' => true,
        ]);

        return $flashSale->fresh();
    }

    /**
     * End a flash sale.
     *
     * @param FlashSale $flashSale
     * @return FlashSale
     */
    public function end(FlashSale $flashSale): FlashSale
    {
        $flashSale->update([
            'status' => FlashSaleStatus::Ended,
            'is_active' => false,
        ]);

        return $flashSale->fresh();
    }

    /**
     * Activate sales whose start time has passed.
     *
     * @return Collection<int, FlashSale>
     */
    public function activateDueSales(): Collection
    {
        $sales = FlashSale::query()
            ->where('status', FlashSaleStatus::Scheduled)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->get();

        return $sales->map(fn(FlashSale $sale): FlashSale => $this->activate($sale));
    }

    /**
     * End sales whose end time has passed.
     *
     * @return Collection<int, FlashSale>
     */
    public function expireEndedSales(): Collection
    {
        $sales = FlashSale::query()
            ->whereIn('status', [FlashSaleStatus::Scheduled, FlashSaleStatus::Active])
            ->where('ends_at', '<=', now())
            ->get();

        return $sales->map(fn(FlashSale $sale): FlashSale => $this->end($sale));
    }
}
