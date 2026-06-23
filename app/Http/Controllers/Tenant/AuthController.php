<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Tenant\TenantUserResource;
use App\Models\Tenant\TenantUser;
use App\Services\Tenant\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Handles tenant store authentication via Sanctum.
 */
class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Authenticate a tenant user and generate a token.
     *
     * @param  Request  $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $this->authService->login(
                $credentials,
                $request->ip(),
                $request->userAgent()
            );
        } catch (RuntimeException $e) {
            return $this->forbidden($e->getMessage());
        }

        return $this->success([
            'user' => new TenantUserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful.');
    }

    /**
     * Log out the authenticated tenant user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = $request->user();

        $this->authService->logout($user);

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Get the authenticated tenant user's profile.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = $request->user()->load('roles', 'permissions');

        return $this->success(new TenantUserResource($user));
    }

    /**
     * Send a password reset OTP.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email', 'exists:users,email']]);

        $this->authService->forgotPassword($request->input('email'));

        return $this->success(null, 'Password reset OTP sent.');
    }

    /**
     * Reset the user's password.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->authService->resetPassword($request->only(['email', 'otp', 'password']));
        } catch (RuntimeException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->success(null, 'Password reset successfully.');
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $updatedUser = $this->authService->updateProfile($user, $validated);

        return $this->success(new TenantUserResource($updatedUser), 'Profile updated successfully.');
    }

    /**
     * Change the authenticated user's password.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            /** @var TenantUser $user */
            $user = $request->user();
            $this->authService->changePassword($user, $request->only(['current_password', 'new_password']));
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }

        return $this->success(null, 'Password changed successfully.');
    }
}
