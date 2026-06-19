<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Tenant\AnalyticsDashboardResource;
use App\Http\Resources\Tenant\DropAnalyticResource;
use App\Models\Tenant\FlashSale;
use App\Services\Tenant\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exposes tenant storefront analytics.
 */
class AnalyticsController extends ApiController
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * Get the analytics dashboard data.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('analytics.view'), 403);

        $data = $this->analyticsService->dashboard(
            $request->date('from'),
            $request->date('to'),
        );

        return $this->success(new AnalyticsDashboardResource($data));
    }

    /**
     * Get analytics for a specific flash sale drop.
     *
     * @param  FlashSale  $flashSale
     * @return JsonResponse
     */
    public function drop(FlashSale $flashSale): JsonResponse
    {
        abort_unless(request()->user()->can('analytics.view'), 403);

        $summary = $this->analyticsService->dropSummary($flashSale);

        return $this->success(new DropAnalyticResource($summary));
    }

    /**
     * Record a page view.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function recordPageView(Request $request): JsonResponse
    {
        $this->analyticsService->recordPageView($request->string('visitor_id')->toString() ?: null);

        return $this->success(null, 'Page view recorded.');
    }
}
