<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal note attached to a customer profile.
 */
class CustomerNote extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'created_by',
        'body',
        'is_pinned',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    /**
     * Get the customer that the note belongs to.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who authored the note.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }
}
