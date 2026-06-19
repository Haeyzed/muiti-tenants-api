<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\CartStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shopping cart for a tenant store customer.
 */
class Cart extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'session_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CartStatus::class,
        ];
    }

    /**
     * Get the customer that owns the cart.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the items in the cart.
     *
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate the subtotal of the cart.
     *
     * @return float
     */
    public function subtotal(): float
    {
        return (float) $this->items->sum(fn (CartItem $item): float => $item->unit_price * $item->quantity);
    }
}
