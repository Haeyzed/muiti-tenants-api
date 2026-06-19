<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\CheckoutSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Individual checkout session within a flash sale waiting room.
 */
class CheckoutSession extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'checkout_queue_id',
        'customer_id',
        'session_token',
        'queue_position',
        'status',
        'expires_at',
        'admitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'queue_position' => 'integer',
            'status' => CheckoutSessionStatus::class,
            'expires_at' => 'datetime',
            'admitted_at' => 'datetime',
        ];
    }

    /**
     * Get the checkout queue this session belongs to.
     *
     * @return BelongsTo<CheckoutQueue, $this>
     */
    public function queue(): BelongsTo
    {
        return $this->belongsTo(CheckoutQueue::class, 'checkout_queue_id');
    }

    /**
     * Get the customer associated with this session.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Determine if the session is currently admitted and not expired.
     *
     * @return bool
     */
    public function isAdmitted(): bool
    {
        return $this->status === CheckoutSessionStatus::Admitted
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}
