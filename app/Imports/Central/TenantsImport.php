<?php

declare(strict_types=1);

namespace App\Imports\Central;

use App\Models\Central\Tenant;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TenantsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Tenant
    {
        return new Tenant([
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
            'email' => (string) $row['email'],
            'phone' => $row['phone'] ?? null,
            'plan' => (string) ($row['plan'] ?? config('billing.default_plan')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:tenants,slug'],
            '*.email' => ['required', 'email', 'max:255'],
            '*.phone' => ['nullable', 'string', 'max:50'],
            '*.plan' => ['nullable', 'string', 'max:100'],
        ];
    }
}
