<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Concerns\Tenant\ResolvesAuthenticatedCustomer;
use App\Enums\Tenant\FlashSaleStatus;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\AttachFlashSaleProductRequest;
use App\Http\Requests\Tenant\StoreFlashSaleRequest;
use App\Http\Requests\Tenant\UpdateFlashSaleRequest;
use App\Http\Resources\Tenant\CheckoutSessionResource;
use App\Http\Resources\Tenant\FlashSaleProductResource;
use App\Http\Resources\Tenant\FlashSaleResource;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\FlashSaleProduct;
use App\Services\Tenant\FlashSaleService;
use App\Services\Tenant\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Throwable;

/**
 * Manages flash sale drops and checkout queues.
 */
class FlashSaleController extends ApiController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly FlashSaleService $flashSaleService,
        private readonly QueueService $queueService,
    ) {}

    /**
     * Get a paginated list of flash sales.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FlashSale::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(FlashSaleStatus::class)],
        ]);

        $sales = $this->flashSaleService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($sales, FlashSaleResource::collection($sales), 'Flash sales retrieved successfully.');
    }

    /**
     * Create a new flash sale.
     *
     * @param StoreFlashSaleRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(StoreFlashSaleRequest $request): JsonResponse
    {
        $this->authorize('create', FlashSale::class);

        $flashSale = $this->flashSaleService->create($request->validated());

        return $this->created(
            new FlashSaleResource($flashSale),
            'Flash sale created successfully.',
        );
    }

    /**
     * Get a single flash sale.
     *
     * @param  FlashSale  $flashSale
     * @return JsonResponse
     */
    public function show(FlashSale $flashSale): JsonResponse
    {
        $this->authorize('view', $flashSale);

        return $this->success(
            new FlashSaleResource($this->flashSaleService->find($flashSale->id)),
            'Flash sale retrieved successfully.',
        );
    }

    /**
     * Update an existing flash sale.
     *
     * @param  UpdateFlashSaleRequest  $request
     * @param  FlashSale  $flashSale
     * @return JsonResponse
     */
    public function update(UpdateFlashSaleRequest $request, FlashSale $flashSale): JsonResponse
    {
        $this->authorize('update', $flashSale);

        $flashSale = $this->flashSaleService->update($flashSale, $request->validated());

        return $this->updated(
            new FlashSaleResource($flashSale),
            'Flash sale updated successfully.',
        );
    }

    /**
     * Delete a flash sale.
     *
     * @param  FlashSale  $flashSale
     * @return JsonResponse
     */
    public function destroy(FlashSale $flashSale): JsonResponse
    {
        $this->authorize('delete', $flashSale);

        $this->flashSaleService->delete($flashSale);

        return $this->deleted('Flash sale deleted successfully.');
    }

    /**
     * Activate a flash sale.
     *
     * @param  FlashSale  $flashSale
     * @return JsonResponse
     */
    public function activate(FlashSale $flashSale): JsonResponse
    {
        $this->authorize('manage', $flashSale);

        $flashSale = $this->flashSaleService->activate($flashSale);
        $this->queueService->ensureQueue($flashSale);

        return $this->success(
            new FlashSaleResource($flashSale),
            'Flash sale activated successfully.',
        );
    }

    /**
     * End a flash sale.
     *
     * @param  FlashSale  $flashSale
     * @return JsonResponse
     */
    public function end(FlashSale $flashSale): JsonResponse
    {
        $this->authorize('manage', $flashSale);

        $flashSale = $this->flashSaleService->end($flashSale);

        return $this->success(
            new FlashSaleResource($flashSale),
            'Flash sale ended successfully.',
        );
    }

    /**
     * Attach a product to a flash sale.
     *
     * @param  AttachFlashSaleProductRequest  $request
     * @param  FlashSale  $flashSale
     * @return JsonResponse
     */
    public function attachProduct(AttachFlashSaleProductRequest $request, FlashSale $flashSale): JsonResponse
    {
        $this->authorize('update', $flashSale);

        $flashSaleProduct = $this->flashSaleService->attachProduct($flashSale, $request->validated());

        return $this->created(
            new FlashSaleProductResource($flashSaleProduct->load('product')),
            'Product attached to flash sale successfully.',
        );
    }

    /**
     * Detach a product from a flash sale.
     *
     * @param  FlashSale  $flashSale
     * @param  FlashSaleProduct  $flashSaleProduct
     * @return JsonResponse
     */
    public function detachProduct(FlashSale $flashSale, FlashSaleProduct $flashSaleProduct): JsonResponse
    {
        $this->authorize('update', $flashSale);

        $this->flashSaleService->detachProduct($flashSale, $flashSaleProduct);

        return $this->deleted('Product removed from flash sale successfully.');
    }

    /**
     * Join the checkout queue for a flash sale.
     *
     * @param FlashSale $flashSale
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function joinQueue(FlashSale $flashSale, Request $request): JsonResponse
    {
        abort_unless($request->user()->can('checkout.join'), 403);
        abort_unless($flashSale->isLive(), 422, 'Flash sale is not currently live.');

        $session = $this->queueService->join(
            $flashSale,
            $this->resolveCustomer($request),
        );

        return $this->created(
            new CheckoutSessionResource($session),
            'Joined checkout queue successfully.',
        );
    }

    /**
     * Get the status of a checkout queue session.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function queueStatus(Request $request): JsonResponse
    {
        $request->validate(['session_token' => ['required', 'string']]);

        $session = $this->queueService->getSessionStatus($request->string('session_token')->toString());

        if ($session === null) {
            return $this->notFound('Checkout session not found.');
        }

        return $this->success(new CheckoutSessionResource($session), 'Checkout session retrieved successfully.');
    }
}
