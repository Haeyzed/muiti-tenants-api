<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Concerns\Tenant\HasSingletonRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Business identity and contact details for a tenant store.
 */
class BusinessSetting extends Model
{
    use HasSingletonRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_name',
        'registration_number',
        'business_type',
        'business_email',
        'business_phone',
        'website',
        'support_email',
        'support_phone',
        'country_code',
        'state_code',
        'city_id',
        'postal_code',
        'address_line_1',
        'address_line_2',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'city_id' => 'integer',
        ];
    }
}
