<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payroll and banking details for a staff member.
 */
class PayrollProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'pay_frequency',
        'currency_code',
        'bank_name',
        'bank_account_number',
        'tax_id',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the staff member that the profile belongs to.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
