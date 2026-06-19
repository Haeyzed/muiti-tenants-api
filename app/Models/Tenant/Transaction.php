<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment provider transaction log entry.
 */
class Transaction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'payment_id',
        'type',
        'amount',
        'status',
        'provider_reference',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
