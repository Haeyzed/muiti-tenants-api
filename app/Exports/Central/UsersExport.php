<?php

declare(strict_types=1);

namespace App\Exports\Central;

use App\Models\Central\CentralUser;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<CentralUser>
 */
class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, CentralUser>  $users
     */
    public function __construct(private readonly Collection $users) {}

    public function collection(): Collection
    {
        return $this->users;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Phone', 'Active', 'Created At'];
    }

    /**
     * @param  CentralUser  $user
     * @return list<string|null>
     */
    public function map($user): array
    {
        return [
            (string) $user->id,
            $user->name,
            $user->email,
            $user->phone,
            $user->is_active ? 'Yes' : 'No',
            $user->created_at?->toDateTimeString(),
        ];
    }
}
