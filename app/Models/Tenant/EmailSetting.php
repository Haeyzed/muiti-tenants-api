<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Concerns\Tenant\HasSingletonRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Email delivery configuration for a tenant store.
 */
class EmailSetting extends Model
{
    use HasSingletonRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sender_name',
        'sender_email',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'templates',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'smtp_port' => 'integer',
            'templates' => 'array',
        ];
    }
}
