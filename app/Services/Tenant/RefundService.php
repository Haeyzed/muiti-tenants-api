<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\OrderStatus;
use App\Enums\Tenant\PaymentStatus;
use App\Enums\Tenant\TransactionType;
use App\Models\Tenant\Order;
use RuntimeException;

/**
 * Handles order refunds.
 */
class RefundService
{
    public function __construct(
        private readonly OrderService       $orderService,
        private readonly PaymentService     $paymentService,
        private readonly TransactionService $transactionService,
    )
    {
    }

    /**
     * Refund an order.
     *
     * @param Order $order
     * @param float|null $amount
     * @param int|null $changedBy
     * @return Order
     * @throws RuntimeException
     */
    public function refund(Order $order, ?float $amount = null, ?int $changedBy = null): Order
    {
        $payment = $order->payment;

        if ($payment === null || $payment->status !== PaymentStatus::Paid) {
            throw new RuntimeException('No paid payment found for this order.');
        }

        $refundAmount = $amount ?? (float)$payment->amount;

        $this->transactionService->log(
            $payment,
            TransactionType::Refund,
            $refundAmount,
            PaymentStatus::Refunded->value,
            providerReference: 'refund_' . uniqid(),
        );

        $payment->update(['status' => PaymentStatus::Refunded]);

        return $this->orderService->updateStatus(
            $order,
            OrderStatus::Refunded,
            "Refunded {$refundAmount}.",
            $changedBy,
        );
    }
}
