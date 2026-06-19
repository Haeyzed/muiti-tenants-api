<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Concerns\Tenant\ResolvesAuthenticatedCustomer;
use App\Enums\Tenant\OrderStatus;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\PlaceOrderRequest;
use App\Http\Requests\Tenant\UpdateOrderStatusRequest;
use App\Http\Resources\Tenant\OrderResource;
use App\Models\Tenant\Order;
use App\Models\Tenant\TenantUser;
use App\Services\Tenant\OrderService;
use App\Services\Tenant\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use RuntimeException;

/**
 * Manages customer orders within a tenant store.
 */
class OrderController extends ApiController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly RefundService $refundService,
    ) {}

    /**
     * Get a paginated list of orders.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        /** @var TenantUser $user */
        $user = $request->user();

        $filters = $request->validate([
            'status' => ['nullable', new Enum(OrderStatus::class)],
        ]);

        if (! $user->can('orders.view')) {
            $filters['customer_id'] = $user->customer?->id;
        }

        $orders = $this->orderService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($orders, OrderResource::collection($orders), 'Orders retrieved successfully.');
    }

    /**
     * Place a new order from the customer's cart.
     *
     * @param PlaceOrderRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        try {
            $order = $this->orderService->placeFromCart(
                $this->resolveCustomer($request),
                $request->validated(),
            );
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        return $this->created(
            new OrderResource($order),
            'Order placed successfully.',
        );
    }

    /**
     * Get a single order.
     *
     * @param  Order  $order
     * @return JsonResponse
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return $this->success(
            new OrderResource($this->orderService->find($order->id)),
            'Order retrieved successfully.',
        );
    }

    /**
     * Update the status of an order.
     *
     * @param  UpdateOrderStatusRequest  $request
     * @param  Order  $order
     * @return JsonResponse
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $status = OrderStatus::from($request->validated('status'));

        $order = $this->orderService->updateStatus(
            $order,
            $status,
            $request->validated('notes'),
            $request->user()?->id,
        );

        return $this->success(
            new OrderResource($this->orderService->find($order->id)),
            'Order status updated successfully.',
        );
    }

    /**
     * Refund an order.
     *
     * @param  Order  $order
     * @return JsonResponse
     */
    public function refund(Order $order): JsonResponse
    {
        logger()->debug('refund.authorize', [
            'user_id' => request()->user()?->id,
            'can_manage' => request()->user()?->can('orders.manage'),
            'order_id' => $order->id,
        ]);

        $this->authorize('refund', $order);

        try {
            $order = $this->refundService->refund($order, changedBy: request()->user()?->id);
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        return $this->success(
            new OrderResource($this->orderService->find($order->id)),
            'Order refunded successfully.',
        );
    }
}
