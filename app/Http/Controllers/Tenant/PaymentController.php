<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Concerns\Tenant\ResolvesAuthenticatedCustomer;
use App\Enums\Tenant\PaymentProvider;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\InitiatePaymentRequest;
use App\Http\Requests\Tenant\PaymentWebhookRequest;
use App\Http\Resources\Tenant\PaymentResource;
use App\Models\Tenant\Order;
use App\Models\Tenant\Payment;
use App\Services\Tenant\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Manages payment initiation and provider webhooks.
 */
class PaymentController extends ApiController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Initiate a payment for an order.
     *
     * @param  InitiatePaymentRequest  $request
     * @param  Order  $order
     * @return JsonResponse
     */
    public function initiate(InitiatePaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('initiate', Payment::class);

        $customer = $this->resolveCustomer($request);

        abort_unless(
            $order->customer_id === $customer->id || $request->user()->can('payments.manage'),
            403,
        );

        try {
            $provider = PaymentProvider::from($request->validated('provider'));
            $payment = $this->paymentService->initiate($order, $provider);
        } catch (RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        }

        return $this->successResponse(
            new PaymentResource($payment),
            'Payment initiated.',
            201,
        );
    }

    /**
     * Get a single payment.
     *
     * @param  Payment  $payment
     * @return JsonResponse
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return $this->successResponse(
            new PaymentResource($this->paymentService->find($payment->id)),
        );
    }

    /**
     * Handle payment provider webhooks.
     *
     * @param  PaymentWebhookRequest  $request
     * @param  string  $provider
     * @return JsonResponse
     */
    public function webhook(PaymentWebhookRequest $request, string $provider): JsonResponse
    {
        $paymentProvider = PaymentProvider::tryFrom($provider);

        if ($paymentProvider === null) {
            return $this->errorResponse('Unsupported payment provider.', 404);
        }

        $payment = $this->paymentService->verifyWebhook(
            $paymentProvider,
            $request->validated('reference'),
            $request->validated(),
        );

        if ($payment === null) {
            return $this->errorResponse('Payment not found.', 404);
        }

        return $this->successResponse(
            new PaymentResource($payment),
            'Payment verified.',
        );
    }
}
