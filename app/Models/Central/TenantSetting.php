<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Key-value settings for a tenant store.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $key
 * @property array<string, mixed>|null $value
 */
class TenantSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
