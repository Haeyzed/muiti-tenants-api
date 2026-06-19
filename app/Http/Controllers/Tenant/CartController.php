<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Concerns\Tenant\ResolvesAuthenticatedCustomer;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\AddCartItemRequest;
use App\Http\Requests\Tenant\UpdateCartItemRequest;
use App\Http\Resources\Tenant\CartResource;
use App\Models\Tenant\Cart;
use App\Models\Tenant\CartItem;
use App\Services\Tenant\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages the authenticated customer's shopping cart.
 */
class CartController extends ApiController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * Show the customer's cart.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $this->authorize('view', Cart::class);

        $cart = $this->cartService->getForCustomer($this->resolveCustomer($request));

        if ($cart === null) {
            return $this->successResponse(null, 'Cart is empty.');
        }

        return $this->successResponse(new CartResource($cart));
    }

    /**
     * Add an item to the cart.
     *
     * @param  AddCartItemRequest  $request
     * @return JsonResponse
     */
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $this->authorize('manage', Cart::class);

        $cart = $this->cartService->addItem(
            $this->resolveCustomer($request),
            $request->validated(),
        );

        return $this->successResponse(
            new CartResource($cart),
            'Item added to cart.',
            201,
        );
    }

    /**
     * Update an item's quantity in the cart.
     *
     * @param  UpdateCartItemRequest  $request
     * @param  CartItem  $item
     * @return JsonResponse
     */
    public function updateItem(UpdateCartItemRequest $request, CartItem $item): JsonResponse
    {
        $this->authorize('updateItem', $item);

        $cart = $this->cartService->updateItemQuantity($item, (int) $request->validated('quantity'));

        return $this->successResponse(
            new CartResource($cart),
            'Cart item updated.',
        );
    }

    /**
     * Remove an item from the cart.
     *
     * @param  CartItem  $item
     * @return JsonResponse
     */
    public function removeItem(CartItem $item): JsonResponse
    {
        $this->authorize('deleteItem', $item);

        $cart = $this->cartService->removeItem($item);

        return $this->successResponse(
            new CartResource($cart),
            'Item removed from cart.',
        );
    }

    /**
     * Clear the cart.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->getForCustomer($this->resolveCustomer($request));

        if ($cart === null) {
            return $this->successResponse(null, 'Cart is already empty.');
        }

        $this->authorize('clear', $cart);
        $this->cartService->clear($cart);

        return $this->successResponse(null, 'Cart cleared.');
    }
}
