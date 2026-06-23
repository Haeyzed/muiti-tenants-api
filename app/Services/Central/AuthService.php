<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Models\Central\CentralUser;
use App\Services\Central\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Handles central platform authentication.
 */
class AuthService
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

    /**
     * Authenticate a central user and generate a token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: CentralUser, token: string}
     *
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function login(array $credentials): array
    {
        /** @var CentralUser|null $user */
        $user = CentralUser::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw new RuntimeException('Your account has been deactivated.', 403);
        }

        $token = $user->createToken('central-api')->plainTextToken;

        return [
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
        ];
    }

    /**
     * Log out the authenticated central user.
     *
     * @param  CentralUser  $user
     * @return void
     */
    public function logout(CentralUser $user): void
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
        $user = CentralUser::query()->where('email', $email)->firstOrFail();
        $otp = $this->otpService->generate($user, 'password_reset');

        // In a real application, you would send the OTP via email/SMS
        // For this example, we'll just log it.
        logger()->info("Password reset OTP for {$user->email}: {$otp->otp}");
    }

    /**
     * Reset the user's password using an OTP.
     *
     * @param  array{email: string, otp: string, password: string}  $data
     * @return void
     */
    public function resetPassword(array $data): void
    {
        $user = CentralUser::query()->where('email', $data['email'])->firstOrFail();

        if (! $this->otpService->verify($user, $data['otp'], 'password_reset')) {
            throw new RuntimeException('Invalid or expired OTP.', 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  CentralUser  $user
     * @param  array<string, mixed>  $data
     * @return CentralUser
     */
    public function updateProfile(CentralUser $user, array $data): CentralUser
    {
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Change the authenticated user's password.
     *
     * @param  CentralUser  $user
     * @param  array{current_password: string, new_password: string}  $data
     * @return void
     */
    public function changePassword(CentralUser $user, array $data): void
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);
    }
}
