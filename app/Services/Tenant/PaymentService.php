<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\OrderStatus;
use App\Enums\Tenant\PaymentProvider;
use App\Enums\Tenant\PaymentStatus;
use App\Enums\Tenant\TransactionType;
use App\Events\Tenant\PaymentCompleted;
use App\Events\Tenant\PaymentInitiated;
use App\Models\Tenant\Order;
use App\Models\Tenant\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Manages payment initiation and verification for tenant orders.
 */
class PaymentService
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly OrderService       $orderService,
    )
    {
    }

    /**
     * Initiate a payment for an order.
     *
     * @param Order $order
     * @param PaymentProvider $provider
     * @return Payment
     * @throws Throwable
     */
    public function initiate(Order $order, PaymentProvider $provider): Payment
    {
        return DB::transaction(function () use ($order, $provider): Payment {
            if ($order->status === OrderStatus::Cancelled) {
                throw new RuntimeException('Cannot pay for a cancelled order.');
            }

            $reference = strtoupper($provider->value) . '_' . Str::upper(Str::random(16));

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'provider' => $provider,
                'provider_reference' => $reference,
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'status' => PaymentStatus::Pending,
                'metadata' => [
                    'initiated_at' => now()->toIso8601String(),
                ],
            ]);

            $this->transactionService->log(
                $payment,
                TransactionType::Authorization,
                (float)$payment->amount,
                PaymentStatus::Pending->value,
                $reference,
            );

            PaymentInitiated::dispatch($payment);

            return $payment->load('transactions');
        });
    }

    /**
     * Verify a payment webhook payload.
     *
     * @param PaymentProvider $provider
     * @param string $reference
     * @param array<string, mixed> $payload
     * @return Payment|null
     */
    public function verifyWebhook(PaymentProvider $provider, string $reference, array $payload): ?Payment
    {
        $payment = Payment::query()
            ->where('provider', $provider)
            ->where('provider_reference', $reference)
            ->first();

        if ($payment === null) {
            return null;
        }

        return $this->markPaid($payment, $payload);
    }

    /**
     * Mark a payment as paid.
     *
     * @param Payment $payment
     * @param array<string, mixed> $payload
     * @return Payment
     * @throws Throwable
     */
    public function markPaid(Payment $payment, array $payload = []): Payment
    {
        return DB::transaction(function () use ($payment, $payload): Payment {
            $payment->update([
                'status' => PaymentStatus::Paid,
                'metadata' => array_merge($payment->metadata ?? [], $payload),
            ]);

            $this->transactionService->log(
                $payment,
                TransactionType::Charge,
                (float)$payment->amount,
                PaymentStatus::Paid->value,
                $payment->provider_reference,
                $payload,
            );

            $this->orderService->updateStatus(
                $payment->order,
                OrderStatus::Confirmed,
                'Payment received.',
            );

            $payment = $payment->fresh(['transactions', 'order']);
            PaymentCompleted::dispatch($payment);

            return $payment;
        });
    }

    /**
     * Find a payment by ID.
     *
     * @param int $id
     * @return Payment
     */
    public function find(int $id): Payment
    {
        return Payment::query()
            ->with(['order', 'transactions'])
            ->findOrFail($id);
    }
}
