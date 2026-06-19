<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Services\Tenant\DropSchedulerService;
use App\Services\Tenant\QueueService;
use Illuminate\Console\Command;

/**
 * Processes flash sale scheduling and queue maintenance across all tenants.
 */
class ProcessFlashSalesCommand extends Command
{
    protected $signature = 'flash-sales:process';

    protected $description = 'Activate due flash sales, expire ended sales, and clean up checkout sessions for all tenants';

    public function handle(DropSchedulerService $scheduler, QueueService $queueService): int
    {
        $activated = 0;
        $ended = 0;
        $expiredSessions = 0;

        Tenant::query()->cursor()->each(function (Tenant $tenant) use ($scheduler, $queueService, &$activated, &$ended, &$expiredSessions): void {
            tenancy()->initialize($tenant);

            $activated += $scheduler->activateDueSales()->count();
            $ended += $scheduler->expireEndedSales()->count();
            $expiredSessions += $queueService->expireStaleSessions();

            tenancy()->end();
        });

        $this->info("Activated: {$activated}, Ended: {$ended}, Expired sessions: {$expiredSessions}");

        return self::SUCCESS;
    }
}
