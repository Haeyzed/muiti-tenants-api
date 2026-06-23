<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\CustomerLoginRequest;
use App\Http\Requests\Tenant\CustomerRegisterRequest;
use App\Http\Resources\Tenant\CustomerAuthResource;
use App\Http\Resources\Tenant\CustomerResource;
use App\Http\Resources\Tenant\TenantUserResource;
use App\Models\Tenant\TenantUser;
use App\Services\Tenant\CustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Handles storefront customer authentication via Sanctum.
 */
class CustomerAuthController extends ApiController
{
    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
    ) {}

    /**
     * Register a new customer.
     *
     * @param CustomerRegisterRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        $result = $this->customerAuthService->register($request->validated());

        return $this->created(
            new CustomerAuthResource($result),
            'Registration successful.'
        );
    }

    /**
     * Log in a customer.
     *
     * @param  CustomerLoginRequest  $request
     * @return JsonResponse
     */
    public function login(CustomerLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->customerAuthService->login($request->validated());
        } catch (RuntimeException $e) {
            return $this->forbidden($e->getMessage());
        }

        return $this->success(
            new CustomerAuthResource($result),
            'Login successful.',
        );
    }

    /**
     * Log out the authenticated customer.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = $request->user();

        $this->customerAuthService->logout($user);

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Get the authenticated customer's profile.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = $request->user()->load('roles', 'customer');

        return $this->success([
            'user' => new TenantUserResource($user),
            'customer' => new CustomerResource($user->customer),
        ]);
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

        $this->customerAuthService->forgotPassword($request->input('email'));

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
            $this->customerAuthService->resetPassword($request->only(['email', 'otp', 'password']));
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
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->customerAuthService->updateProfile($user, $validated);

        return $this->success([
            'user' => new TenantUserResource($result['user']),
            'customer' => new CustomerResource($result['customer']),
        ], 'Profile updated successfully.');
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
            $this->customerAuthService->changePassword($user, $request->only(['current_password', 'new_password']));
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }

        return $this->success(null, 'Password changed successfully.');
    }
}
