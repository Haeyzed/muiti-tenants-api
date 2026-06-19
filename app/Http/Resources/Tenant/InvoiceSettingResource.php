<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\InvoiceSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InvoiceSetting
 */
class InvoiceSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'prefix' => $this->prefix,
            'number_format' => $this->number_format,
            'footer' => $this->footer,
            'notes' => $this->notes,
            'next_sequence' => $this->next_sequence,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
