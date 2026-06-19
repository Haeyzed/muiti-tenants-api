<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending invitation for a user to join the tenant team.
 */
class TeamInvitation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'token',
        'role',
        'permissions',
        'invited_by',
        'expires_at',
        'accepted_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TenantUser, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->cancelled_at === null
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && $this->accepted_at === null;
    }
}
