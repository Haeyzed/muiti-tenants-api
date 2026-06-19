<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\PaymentProvider;
use App\Enums\Tenant\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payment record for an order.
 */
class Payment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'amount',
        'currency',
        'status',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the order associated with the payment.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the transactions related to the payment.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope a query to filter payments by status.
     *
     * @param  Builder<Payment>  $query
     * @param  string|PaymentStatus  $status
     * @return Builder<Payment>
     */
    public function scopeSearch(Builder $query, string|PaymentStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
