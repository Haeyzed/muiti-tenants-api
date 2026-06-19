<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Nnjeim\World\Geolocate\Exceptions\DatabaseNotFoundException;
use Nnjeim\World\Geolocate\Exceptions\GeolocateException;
use Nnjeim\World\Geolocate\GeolocateService;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\Currency;
use Nnjeim\World\Models\Language;
use Nnjeim\World\Models\State;
use Nnjeim\World\Models\Timezone;

/**
 * Provides World reference data with consistent value/label option formatting.
 */
class WorldService
{
    public function __construct(
        private readonly GeolocateService $geolocateService,
    ) {}

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    public function countryOptions(?string $search = null): Collection
    {
        $query = Country::query()->orderBy('name');

        if ($search !== null) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('iso2', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        return $query->get()->map(fn (Country $country): array => [
            'value' => $country->iso2,
            'label' => $country->name,
        ])->values();
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    public function stateOptions(?string $countryCode = null, ?string $search = null): Collection
    {
        $query = State::query()->orderBy('name');

        if ($countryCode !== null) {
            $query->where('country_code', $countryCode);
        }

        if ($search !== null) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query->get()->map(fn (State $state): array => [
            'value' => $state->state_code ?? (string) $state->id,
            'label' => $state->name,
        ])->values();
    }

    /**
     * @return Collection<int, array{value: int, label: string}>
     */
    public function cityOptions(?string $countryCode = null, ?string $stateCode = null, ?string $search = null): Collection
    {
        $query = City::query()->orderBy('name');

        if ($countryCode !== null) {
            $query->where('country_code', $countryCode);
        }

        if ($stateCode !== null) {
            $query->where('state_code', $stateCode);
        }

        if ($search !== null) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query->get()->map(fn (City $city): array => [
            'value' => $city->id,
            'label' => $city->name,
        ])->values();
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    public function currencyOptions(?string $search = null): Collection
    {
        $query = Currency::query()->orderBy('name');

        if ($search !== null) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        return $query->get()->map(function (Currency $currency): array {
            $label = $currency->name;

            if ($currency->symbol !== null && $currency->symbol !== '') {
                $label .= ' ('.$currency->symbol.')';
            }

            return [
                'value' => $currency->code,
                'label' => $label,
            ];
        })->values();
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    public function languageOptions(?string $search = null): Collection
    {
        $query = Language::query()->orderBy('name');

        if ($search !== null) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        return $query->get()->map(fn (Language $language): array => [
            'value' => $language->code,
            'label' => $language->name,
        ])->values();
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    public function timezoneOptions(?string $countryCode = null, ?string $search = null): Collection
    {
        $query = Timezone::query()->orderBy('name');

        if ($countryCode !== null) {
            $countryId = Country::query()->where('iso2', $countryCode)->value('id');

            if ($countryId !== null) {
                $query->where('country_id', $countryId);
            }
        }

        if ($search !== null) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query->get()->map(fn (Timezone $timezone): array => [
            'value' => $timezone->name,
            'label' => $timezone->name,
        ])->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function geolocate(?string $ip = null): ?array
    {
        try {
            $resolvedIp = $ip ?? $this->geolocateService->resolveClientIp();

            return $this->buildGeolocationResult(
                $this->geolocateService->geolocate($resolvedIp),
            );
        } catch (DatabaseNotFoundException|GeolocateException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $geoData
     * @return array<string, mixed>
     */
    private function buildGeolocationResult(array $geoData): array
    {
        $country = $this->resolveCountryFromGeodata($geoData);
        $state = $this->resolveStateFromGeodata($geoData, $country);
        $city = $this->resolveCityFromGeodata($geoData, $country, $state);
        $timezone = $this->resolveTimezoneFromGeodata($geoData, $country);

        return [
            'ip' => $geoData['ip'],
            'country' => $country !== null ? [
                'id' => $country->id,
                'iso2' => $country->iso2,
                'iso3' => $country->iso3,
                'name' => $country->name,
                'phone_code' => $country->phone_code,
                'region' => $country->region,
                'subregion' => $country->subregion,
            ] : null,
            'state' => $state !== null ? [
                'id' => $state->id,
                'name' => $state->name,
                'state_code' => $state->state_code ?? $geoData['state_code'] ?? null,
            ] : [
                'name' => $geoData['state_name'] ?? null,
                'state_code' => $geoData['state_code'] ?? null,
            ],
            'city' => $city !== null ? [
                'id' => $city->id,
                'name' => $city->name,
            ] : [
                'name' => $geoData['city_name'] ?? null,
            ],
            'coordinates' => [
                'latitude' => $geoData['latitude'] ?? null,
                'longitude' => $geoData['longitude'] ?? null,
                'accuracy_radius' => $geoData['accuracy_radius'] ?? null,
            ],
            'timezone' => $timezone !== null ? [
                'id' => $timezone->id,
                'name' => $timezone->name,
            ] : [
                'name' => $geoData['timezone'] ?? null,
            ],
            'postal_code' => $geoData['postal_code'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $geoData
     */
    private function resolveCountryFromGeodata(array $geoData): ?Country
    {
        if (empty($geoData['country_code'])) {
            return null;
        }

        return Country::query()->where('iso2', $geoData['country_code'])->first();
    }

    /**
     * @param  array<string, mixed>  $geoData
     */
    private function resolveStateFromGeodata(array $geoData, ?Country $country): ?State
    {
        if ($country === null || (empty($geoData['state_code']) && empty($geoData['state_name']))) {
            return null;
        }

        $query = State::query()->where('country_id', $country->id);

        $statesTable = config('world.migrations.states.table_name', 'states');
        $hasStateCode = config('world.migrations.states.optional_fields.state_code.required', false)
            || Schema::hasColumn($statesTable, 'state_code');

        if ($hasStateCode && ! empty($geoData['state_code'])) {
            $query->where(function ($builder) use ($geoData): void {
                $builder->where('state_code', $geoData['state_code'])
                    ->orWhere('name', $geoData['state_name']);
            });
        } else {
            $query->where('name', $geoData['state_name']);
        }

        return $query->first();
    }

    /**
     * @param  array<string, mixed>  $geoData
     */
    private function resolveCityFromGeodata(array $geoData, ?Country $country, ?State $state): ?City
    {
        if ($country === null || empty($geoData['city_name'])) {
            return null;
        }

        $query = City::query()
            ->where('country_id', $country->id)
            ->where('name', 'like', $geoData['city_name'].'%');

        if ($state !== null) {
            $query->where('state_id', $state->id);
        }

        return $query->first();
    }

    /**
     * @param  array<string, mixed>  $geoData
     */
    private function resolveTimezoneFromGeodata(array $geoData, ?Country $country): ?Timezone
    {
        if ($country === null || empty($geoData['timezone'])) {
            return null;
        }

        return Timezone::query()
            ->where('country_id', $country->id)
            ->where('name', $geoData['timezone'])
            ->first();
    }
}
