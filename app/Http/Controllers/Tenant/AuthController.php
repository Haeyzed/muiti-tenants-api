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
            return $this->forbidden($e->getMessage(), $e->getCode() ?: 403);
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

        return $this->success(new TenantUserResource($user), 'User retrieved successfully');
    }
}
