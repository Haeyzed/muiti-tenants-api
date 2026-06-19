<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Central\CentralUserResource;
use App\Models\Central\CentralUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles central platform authentication via Sanctum.
 */
class AuthController extends ApiController
{
    /**
     * Authenticate a central user and generate a token.
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

        /** @var CentralUser|null $user */
        $user = CentralUser::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return $this->forbidden('Your account has been deactivated.', 403);
        }

        $token = $user->createToken('central-api')->plainTextToken;

        return $this->success([
            'user' => new CentralUserResource($user->load('roles', 'permissions')),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Log out the authenticated central user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Get the authenticated central user's profile.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        /** @var CentralUser $user */
        $user = $request->user()->load('roles', 'permissions');

        return $this->success(new CentralUserResource($user), 'User retrieved successfully');
    }
}
