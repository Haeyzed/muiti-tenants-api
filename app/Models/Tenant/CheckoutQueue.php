<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\CheckoutSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Virtual waiting room queue for high-traffic flash sale checkouts.
 */
class CheckoutQueue extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'flash_sale_id',
        'name',
        'max_concurrent_sessions',
        'session_ttl_seconds',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_concurrent_sessions' => 'integer',
            'session_ttl_seconds' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the flash sale associated with the queue.
     *
     * @return BelongsTo<FlashSale, $this>
     */
    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    /**
     * Get the checkout sessions in the queue.
     *
     * @return HasMany<CheckoutSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CheckoutSession::class);
    }

    /**
     * Get the number of active sessions in the queue.
     *
     * @return int
     */
    public function activeSessionCount(): int
    {
        return $this->sessions()
            ->where('status', CheckoutSessionStatus::Admitted)
            ->where('expires_at', '>', now())
            ->count();
    }
}
