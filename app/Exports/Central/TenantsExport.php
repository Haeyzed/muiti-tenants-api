<?php

declare(strict_types=1);

namespace App\Exports\Central;

use App\Models\Central\Tenant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Tenant>
 */
class TenantsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Tenant>  $tenants
     */
    public function __construct(private readonly Collection $tenants) {}

    public function collection(): Collection
    {
        return $this->tenants;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Slug', 'Email', 'Phone', 'Plan', 'Status', 'Created At'];
    }

    /**
     * @param  Tenant  $tenant
     * @return list<string|null>
     */
    public function map($tenant): array
    {
        return [
            $tenant->id,
            $tenant->name,
            $tenant->slug,
            $tenant->email,
            $tenant->phone,
            $tenant->plan,
            $tenant->status->value ?? (string) $tenant->status,
            $tenant->created_at?->toDateTimeString(),
        ];
    }
}
