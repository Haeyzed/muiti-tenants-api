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
     * @param  CustomerRegisterRequest  $request
     * @return JsonResponse
     */
    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        $result = $this->customerAuthService->register($request->validated());

        return $this->successResponse(
            new CustomerAuthResource($result),
            'Registration successful.',
            201,
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
        $result = $this->customerAuthService->login($request->validated());

        return $this->successResponse(
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

        return $this->successResponse(null, 'Logged out successfully.');
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
        $user = $request->user()->load('roles');

        return $this->successResponse([
            'user' => new TenantUserResource($user),
            'customer' => new CustomerResource($user->customer),
        ]);
    }
}
