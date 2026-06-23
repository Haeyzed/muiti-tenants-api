<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One-Time Password (OTP) for various purposes.
 */
class Otp extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_type',
        'otp',
        'purpose',
        'expires_at',
        'used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Get the user that the OTP belongs to.
     *
     * @return MorphTo
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
