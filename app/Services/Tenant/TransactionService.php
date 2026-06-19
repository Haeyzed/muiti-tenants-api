<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\TransactionType;
use App\Models\Tenant\Payment;
use App\Models\Tenant\Transaction;

/**
 * Logs payment provider transactions.
 */
class TransactionService
{
    /**
     * Log a payment transaction.
     *
     * @param Payment $payment
     * @param TransactionType $type
     * @param float $amount
     * @param string $status
     * @param string|null $providerReference
     * @param array<string, mixed> $payload
     * @return Transaction
     */
    public function log(
        Payment         $payment,
        TransactionType $type,
        float           $amount,
        string          $status,
        ?string         $providerReference = null,
        array           $payload = [],
    ): Transaction
    {
        return $payment->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'status' => $status,
            'provider_reference' => $providerReference,
            'payload' => $payload,
        ]);
    }
}
