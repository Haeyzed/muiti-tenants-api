<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Paddle\Billable;

/**
 * Paddle billable account linked to a central tenant.
 */
class PaddleBillingAccount extends Model
{
    use Billable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function paddleName(): string
    {
        return $this->tenant->name;
    }

    public function paddleEmail(): string
    {
        return $this->tenant->email ?? '';
    }
}
