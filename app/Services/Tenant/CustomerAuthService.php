<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\CustomerCreated;
use App\Models\Tenant\Customer;
use App\Models\Tenant\TenantUser;
use App\Services\Tenant\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Handles storefront customer registration and authentication.
 */
class CustomerAuthService
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

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
                'password' => Hash::make($data['password']),
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
            throw new RuntimeException('Your account has been deactivated.', 403);
        }

        if ($user->isSuspended()) {
            throw new RuntimeException('Your account has been suspended.', 403);
        }

        if (!$user->hasRole('customer')) {
            throw new RuntimeException('This account is not registered as a storefront customer.', 403);
        }

        $customer = $user->customer;

        if ($customer === null) {
            throw new RuntimeException('Customer profile not found for this account.', 404);
        }

        if (!$customer->is_active) {
            throw new RuntimeException('Your customer profile has been deactivated.', 403);
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

    /**
     * Send a password reset OTP to the user.
     *
     * @param  string  $email
     * @return void
     */
    public function forgotPassword(string $email): void
    {
        $user = TenantUser::query()->where('email', $email)->whereHas('roles', fn ($q) => $q->where('name', 'customer'))->firstOrFail();
        $otp = $this->otpService->generate($user, 'password_reset');

        logger()->info("Password reset OTP for customer {$user->email}: {$otp->otp}");
    }

    /**
     * Reset the user's password using an OTP.
     *
     * @param  array{email: string, otp: string, password: string}  $data
     * @return void
     */
    public function resetPassword(array $data): void
    {
        $user = TenantUser::query()->where('email', $data['email'])->whereHas('roles', fn ($q) => $q->where('name', 'customer'))->firstOrFail();

        if (! $this->otpService->verify($user, $data['otp'], 'password_reset')) {
            throw new RuntimeException('Invalid or expired OTP.', 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  TenantUser  $user
     * @param  array<string, mixed>  $data
     * @return array{user: TenantUser, customer: Customer}
     */
    public function updateProfile(TenantUser $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update($data);
            $user->customer->update($data);

            return [
                'user' => $user->fresh(),
                'customer' => $user->customer->fresh(),
            ];
        });
    }

    /**
     * Change the authenticated user's password.
     *
     * @param  TenantUser  $user
     * @param  array{current_password: string, new_password: string}  $data
     * @return void
     */
    public function changePassword(TenantUser $user, array $data): void
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);
    }
}
