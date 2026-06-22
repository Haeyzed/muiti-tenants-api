<?php

declare(strict_types=1);

namespace App\Models\Central;

use App\Concerns\Central\HasSingletonRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Invoice numbering and formatting settings for the central application.
 */
class InvoiceSetting extends Model
{
    use HasSingletonRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'prefix',
        'number_format',
        'footer',
        'notes',
        'next_sequence',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_sequence' => 'integer',
        ];
    }
}
