<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Concerns\Tenant\HasSingletonRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Store operational settings for a tenant.
 */
class StoreSetting extends Model
{
    use HasSingletonRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_name',
        'store_description',
        'contact_email',
        'contact_phone',
        'currency_code',
        'timezone',
        'language_code',
    ];
}
