<?php

declare(strict_types=1);

namespace App\Models\Central;

use App\Enums\Central\BillingProvider;
use App\Enums\Central\PlatformSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gateway-managed platform subscription for a tenant.
 */
class PlatformSubscription extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'provider',
        'plan_slug',
        'provider_customer_id',
        'provider_subscription_id',
        'provider_plan_id',
        'status',
        'authorization_url',
        'trial_ends_at',
        'ends_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => BillingProvider::class,
            'status' => PlatformSubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            PlatformSubscriptionStatus::Active,
            PlatformSubscriptionStatus::Trialing,
        ], true);
    }
}
