<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Services\Tenant\WorldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exposes World reference data for tenant store configuration.
 */
class WorldController extends ApiController
{
    public function __construct(
        private readonly WorldService $worldService,
    ) {}

    /**
     * Get a list of countries.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function countries(Request $request): JsonResponse
    {
        return $this->success(
            $this->worldService->countryOptions($request->string('search')->toString() ?: null),
        );
    }

    /**
     * Get a list of states for a given country.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function states(Request $request): JsonResponse
    {
        return $this->success(
            $this->worldService->stateOptions(
                $request->string('country_code')->toString() ?: null,
                $request->string('search')->toString() ?: null,
            ),
        );
    }

    /**
     * Get a list of cities for a given country and state.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function cities(Request $request): JsonResponse
    {
        return $this->success(
            $this->worldService->cityOptions(
                $request->string('country_code')->toString() ?: null,
                $request->string('state_code')->toString() ?: null,
                $request->string('search')->toString() ?: null,
            ),
        );
    }

    /**
     * Get a list of currencies.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function currencies(Request $request): JsonResponse
    {
        return $this->success(
            $this->worldService->currencyOptions($request->string('search')->toString() ?: null),
        );
    }

    /**
     * Get a list of languages.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function languages(Request $request): JsonResponse
    {
        return $this->success(
            $this->worldService->languageOptions($request->string('search')->toString() ?: null),
        );
    }

    /**
     * Get a list of timezones.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function timezones(Request $request): JsonResponse
    {
        return $this->success(
            $this->worldService->timezoneOptions(
                $request->string('country_code')->toString() ?: null,
                $request->string('search')->toString() ?: null,
            ),
        );
    }

    /**
     * Geolocate the request's IP address.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function geolocate(Request $request): JsonResponse
    {
        $data = $this->worldService->geolocate($request->ip());

        if ($data === null) {
            return $this->notFound('Unable to geolocate request.');
        }

        return $this->success($data);
    }
}
