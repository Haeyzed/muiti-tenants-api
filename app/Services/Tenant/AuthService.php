<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\TenantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Handles tenant store authentication.
 */
class AuthService
{
    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    /**
     * Authenticate a tenant user and generate a token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @param  string|null  $ipAddress
     * @param  string|null  $userAgent
     * @return array{user: TenantUser, token: string}
     *
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function login(array $credentials, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        /** @var TenantUser|null $user */
        $user = TenantUser::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw new RuntimeException('Your account has been deactivated.', 403);
        }

        if ($user->isSuspended()) {
            throw new RuntimeException('Your account has been suspended.', 403);
        }

        if ($user->roles->count() === 1 && $user->hasRole('customer')) {
            throw new RuntimeException('Please use the customer login endpoint.', 403);
        }

        $this->teamService->recordLogin(
            $user,
            $ipAddress,
            $userAgent,
        );

        $token = $user->createToken('tenant-api')->plainTextToken;

        return [
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
        ];
    }

    /**
     * Log out the authenticated tenant user.
     *
     * @param  TenantUser  $user
     * @return void
     */
    public function logout(TenantUser $user): void
    {
        $loginHistory = $user->loginHistories()
            ->whereNull('logged_out_at')
            ->latest('logged_in_at')
            ->first();

        if ($loginHistory !== null) {
            $this->teamService->recordLogout($loginHistory);
        }

        $user->currentAccessToken()?->delete();
    }
}
