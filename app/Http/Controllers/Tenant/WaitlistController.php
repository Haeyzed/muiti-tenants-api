<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\Tenant\WaitlistType;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\JoinWaitlistRequest;
use App\Http\Resources\Tenant\WaitlistResource;
use App\Http\Resources\Tenant\WaitlistSubscriberResource;
use App\Models\Tenant\Product;
use App\Models\Tenant\Waitlist;
use App\Models\Tenant\WaitlistSubscriber;
use App\Services\Tenant\WaitlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages product and flash sale waitlists.
 */
class WaitlistController extends ApiController
{
    public function __construct(
        private readonly WaitlistService $waitlistService,
    ) {}

    /**
     * Get a paginated list of waitlists.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Waitlist::class);

        $filters = $request->validate([
            'product_id' => ['nullable', 'integer'],
        ]);

        $waitlists = $this->waitlistService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($waitlists, WaitlistResource::collection($waitlists), 'Waitlists retrieved successfully.');
    }

    /**
     * Join a waitlist for a product.
     *
     * @param  JoinWaitlistRequest  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function join(JoinWaitlistRequest $request, Product $product): JsonResponse
    {
        abort_unless($request->user()->can('waitlists.join'), 403);

        $type = WaitlistType::tryFrom($request->string('type', WaitlistType::BackInStock->value)->toString())
            ?? WaitlistType::BackInStock;

        $customer = $request->user()->customer;

        $subscriber = $this->waitlistService->subscribe(
            $product,
            $request->validated('email'),
            $type,
            $request->integer('flash_sale_id') ?: null,
            $customer,
        );

        return $this->created(
            new WaitlistSubscriberResource($subscriber),
            'Joined waitlist successfully.',
        );
    }

    /**
     * Leave a waitlist.
     *
     * @param  WaitlistSubscriber  $subscriber
     * @return JsonResponse
     */
    public function leave(WaitlistSubscriber $subscriber): JsonResponse
    {
        $this->authorize('unsubscribe', $subscriber);

        $this->waitlistService->unsubscribe($subscriber);

        return $this->deleted('Left waitlist successfully.');
    }
}
