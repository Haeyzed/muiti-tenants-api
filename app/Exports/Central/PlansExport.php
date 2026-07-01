<?php

declare(strict_types=1);

namespace App\Exports\Central;

use App\Models\Central\Plan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Plan>
 */
class PlansExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Plan>  $plans
     */
    public function __construct(private readonly Collection $plans) {}

    public function collection(): Collection
    {
        return $this->plans;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Price',
            'Currency',
            'Interval',
            'Active',
            'Featured',
            'Sort Order',
            'Created At',
        ];
    }

    /**
     * @param  Plan  $plan
     * @return list<string|null>
     */
    public function map($plan): array
    {
        return [
            (string) $plan->id,
            $plan->name,
            $plan->slug,
            (string) $plan->price,
            $plan->currency,
            $plan->interval,
            $plan->is_active ? 'Yes' : 'No',
            $plan->is_featured ? 'Yes' : 'No',
            (string) $plan->sort_order,
            $plan->created_at?->toDateTimeString(),
        ];
    }
}
