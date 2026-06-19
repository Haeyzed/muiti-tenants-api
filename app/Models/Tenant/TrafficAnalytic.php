<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Daily storefront traffic metrics.
 */
class TrafficAnalytic extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'recorded_on',
        'page_views',
        'unique_visitors',
        'bounce_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'page_views' => 'integer',
            'unique_visitors' => 'integer',
            'bounce_count' => 'integer',
        ];
    }
}
