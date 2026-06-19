<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\ConversionMetric;
use App\Models\Tenant\DropAnalytic;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Order;
use App\Models\Tenant\Payment;
use App\Models\Tenant\TrafficAnalytic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Records and aggregates tenant storefront analytics.
 */
class AnalyticsService
{
    /**
     * Record a new queue entry for a flash sale.
     *
     * @param int $flashSaleId
     * @return void
     */
    public function recordQueueEntry(int $flashSaleId): void
    {
        $this->dropAnalyticFor($flashSaleId)->increment('queue_entries');

        $today = today();
        $metric = ConversionMetric::query()->firstOrCreate(
            ['flash_sale_id' => $flashSaleId, 'recorded_on' => $today],
            ['visitors' => 0, 'conversions' => 0, 'conversion_rate' => 0],
        );
        $metric->increment('visitors');
    }

    /**
     * Record an order placed.
     *
     * @param Order $order
     * @return void
     */
    public function recordOrderPlaced(Order $order): void
    {
        $unitsSold = (int)$order->items->sum('quantity');

        if ($order->flash_sale_id !== null) {
            $drop = $this->dropAnalyticFor((int)$order->flash_sale_id);
            $drop->increment('units_sold', $unitsSold);
            $this->incrementConversion((int)$order->flash_sale_id);
        }

        $this->recordTrafficHit();
    }

    /**
     * Record a completed payment.
     *
     * @param Payment $payment
     * @return void
     */
    public function recordPaymentCompleted(Payment $payment): void
    {
        $order = $payment->order;

        if ($order->flash_sale_id !== null) {
            $drop = $this->dropAnalyticFor((int)$order->flash_sale_id);
            $drop->increment('checkouts_completed');
            $drop->increment('revenue', (float)$payment->amount);
        }
    }

    /**
     * Record a page view.
     *
     * @param string|null $visitorId
     * @return void
     */
    public function recordPageView(?string $visitorId = null): void
    {
        $today = today();
        $metric = TrafficAnalytic::query()->firstOrCreate(
            ['recorded_on' => $today],
            ['page_views' => 0, 'unique_visitors' => 0, 'bounce_count' => 0],
        );

        $metric->increment('page_views');

        if ($visitorId !== null) {
            $metric->increment('unique_visitors');
        }
    }

    /**
     * Retrieve the dashboard analytics.
     *
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array<string, mixed>
     */
    public function dashboard(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now()->endOfDay();

        $traffic = TrafficAnalytic::query()
            ->whereBetween('recorded_on', [$from->toDateString(), $to->toDateString()])
            ->get();

        $drops = DropAnalytic::query()
            ->with('flashSale:id,name')
            ->get();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'traffic' => [
                'page_views' => $traffic->sum('page_views'),
                'unique_visitors' => $traffic->sum('unique_visitors'),
                'bounce_count' => $traffic->sum('bounce_count'),
            ],
            'drops' => $drops->map(fn(DropAnalytic $drop): array => [
                'flash_sale_id' => $drop->flash_sale_id,
                'flash_sale_name' => $drop->flashSale?->name,
                'queue_entries' => $drop->queue_entries,
                'checkouts_completed' => $drop->checkouts_completed,
                'revenue' => $drop->revenue,
                'units_sold' => $drop->units_sold,
            ])->values()->all(),
            'conversion' => $this->conversionSummary($from, $to),
        ];
    }

    /**
     * Retrieve the drop summary analytics.
     *
     * @param FlashSale $flashSale
     * @return array<string, mixed>
     */
    public function dropSummary(FlashSale $flashSale): array
    {
        $drop = DropAnalytic::query()
            ->where('flash_sale_id', $flashSale->id)
            ->first();

        $conversion = ConversionMetric::query()
            ->where('flash_sale_id', $flashSale->id)
            ->orderByDesc('recorded_on')
            ->limit(30)
            ->get();

        return [
            'flash_sale_id' => $flashSale->id,
            'drop' => $drop,
            'conversion_trend' => $conversion,
        ];
    }

    /**
     * Aggregate daily analytics.
     *
     * @return int
     */
    public function aggregateDaily(): int
    {
        $updated = 0;

        ConversionMetric::query()
            ->where('recorded_on', '<', today())
            ->where('conversion_rate', 0)
            ->where('visitors', '>', 0)
            ->each(function (ConversionMetric $metric) use (&$updated): void {
                $metric->update([
                    'conversion_rate' => round($metric->conversions / $metric->visitors, 4),
                ]);
                $updated++;
            });

        return $updated;
    }

    /**
     * Retrieve or create a drop analytic for a flash sale.
     *
     * @param int $flashSaleId
     * @return DropAnalytic
     */
    private function dropAnalyticFor(int $flashSaleId): DropAnalytic
    {
        return DropAnalytic::query()->firstOrCreate(
            ['flash_sale_id' => $flashSaleId],
            [
                'queue_entries' => 0,
                'checkouts_completed' => 0,
                'revenue' => 0,
                'units_sold' => 0,
            ],
        );
    }

    /**
     * Increment the conversion for a flash sale.
     *
     * @param int $flashSaleId
     * @return void
     * @throws Throwable
     */
    private function incrementConversion(int $flashSaleId): void
    {
        $today = today();

        DB::transaction(function () use ($flashSaleId, $today): void {
            $metric = ConversionMetric::query()->firstOrCreate(
                ['flash_sale_id' => $flashSaleId, 'recorded_on' => $today],
                ['visitors' => 0, 'conversions' => 0, 'conversion_rate' => 0],
            );

            $metric->increment('conversions');

            if ($metric->visitors > 0) {
                $metric->update([
                    'conversion_rate' => round($metric->conversions / $metric->visitors, 4),
                ]);
            }
        });
    }

    /**
     * Record traffic hit.
     *
     * @return void
     */
    private function recordTrafficHit(): void
    {
        $this->recordPageView();
    }

    /**
     * Retrieve the conversion summary.
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return Collection<int, ConversionMetric>
     */
    private function conversionSummary(Carbon $from, Carbon $to): Collection
    {
        return ConversionMetric::query()
            ->whereBetween('recorded_on', [$from->toDateString(), $to->toDateString()])
            ->orderBy('recorded_on')
            ->get();
    }
}
