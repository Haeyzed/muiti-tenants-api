<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\CustomerCreated;
use App\Models\Tenant\Customer;
use App\Models\Tenant\TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Handles storefront customer registration and authentication.
 */
class CustomerAuthService
{
    /**
     * Register a new customer.
     *
     * @param array<string, mixed> $data
     * @return array{user: TenantUser, customer: Customer, token: string}
     * @throws Throwable
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = TenantUser::query()->create([
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->assignRole('customer');

            $customer = Customer::query()->create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);

            CustomerCreated::dispatch($customer->load('user'));

            $token = $user->createToken('customer-api')->plainTextToken;

            return [
                'user' => $user->load('roles'),
                'customer' => $customer,
                'token' => $token,
            ];
        });
    }

    /**
     * Authenticate a customer.
     *
     * @param array{email: string, password: string} $credentials
     * @return array{user: TenantUser, customer: Customer, token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        /** @var TenantUser|null $user */
        $user = TenantUser::query()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        if ($user->isSuspended()) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended.'],
            ]);
        }

        if (!$user->hasRole('customer')) {
            throw ValidationException::withMessages([
                'email' => ['This account is not registered as a storefront customer.'],
            ]);
        }

        $customer = $user->customer;

        if ($customer === null) {
            throw ValidationException::withMessages([
                'email' => ['Customer profile not found for this account.'],
            ]);
        }

        if (!$customer->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your customer profile has been deactivated.'],
            ]);
        }

        $token = $user->createToken('customer-api')->plainTextToken;

        return [
            'user' => $user->load('roles'),
            'customer' => $customer,
            'token' => $token,
        ];
    }

    /**
     * Log out a customer.
     *
     * @param TenantUser $user
     * @return void
     */
    public function logout(TenantUser $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
