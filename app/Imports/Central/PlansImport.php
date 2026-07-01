<?php

declare(strict_types=1);

namespace App\Imports\Central;

use App\Models\Central\Plan;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PlansImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Plan
    {
        return new Plan([
            'slug' => (string) $row['slug'],
            'name' => (string) $row['name'],
            'description' => $row['description'] ?? null,
            'price' => (float) ($row['price'] ?? 0),
            'currency' => (string) ($row['currency'] ?? 'USD'),
            'interval' => (string) ($row['interval'] ?? 'monthly'),
            'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_featured' => filter_var($row['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:plans,slug'],
            '*.name' => ['required', 'string', 'max:150'],
            '*.price' => ['required', 'numeric', 'min:0'],
            '*.currency' => ['nullable', 'string', 'size:3'],
            '*.interval' => ['nullable', 'in:monthly,yearly'],
            '*.is_active' => ['nullable'],
            '*.is_featured' => ['nullable'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
