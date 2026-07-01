<?php

declare(strict_types=1);

namespace App\Imports\Central;

use App\Models\Central\CentralUser;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): CentralUser
    {
        return new CentralUser([
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'phone' => $row['phone'] ?? null,
            'password' => Hash::make((string) ($row['password'] ?? 'password123')),
            'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            '*.phone' => ['nullable', 'string', 'max:50'],
            '*.password' => ['nullable', 'string', 'min:8'],
            '*.is_active' => ['nullable'],
        ];
    }
}
