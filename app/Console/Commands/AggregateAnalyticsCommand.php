<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Services\Tenant\AnalyticsService;
use Illuminate\Console\Command;

/**
 * Aggregates daily analytics metrics across all tenant stores.
 */
class AggregateAnalyticsCommand extends Command
{
    protected $signature = 'analytics:aggregate';

    protected $description = 'Roll up conversion rates and daily analytics for all tenants';

    public function handle(AnalyticsService $analyticsService): int
    {
        $total = 0;

        Tenant::query()->cursor()->each(function (Tenant $tenant) use ($analyticsService, &$total): void {
            tenancy()->initialize($tenant);
            $total += $analyticsService->aggregateDaily();
            tenancy()->end();
        });

        $this->info("Aggregated {$total} conversion metric rows.");

        return self::SUCCESS;
    }
}
