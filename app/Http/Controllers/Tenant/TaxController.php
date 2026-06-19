<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\CalculateTaxRequest;
use App\Http\Requests\Tenant\StoreTaxClassRequest;
use App\Http\Requests\Tenant\StoreTaxRateRequest;
use App\Http\Requests\Tenant\StoreTaxRegionRequest;
use App\Http\Requests\Tenant\StoreTaxRuleRequest;
use App\Http\Requests\Tenant\UpdateTaxClassRequest;
use App\Http\Resources\Tenant\TaxClassResource;
use App\Http\Resources\Tenant\TaxRateResource;
use App\Http\Resources\Tenant\TaxRegionResource;
use App\Http\Resources\Tenant\TaxRuleResource;
use App\Models\Tenant\TaxClass;
use App\Models\Tenant\TaxRate;
use App\Models\Tenant\TaxRule;
use App\Services\Tenant\TaxRateService;
use App\Services\Tenant\TaxRuleService;
use App\Services\Tenant\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Manages tax configuration and calculation.
 */
class TaxController extends ApiController
{
    public function __construct(
        private readonly TaxService $taxService,
        private readonly TaxRateService $taxRateService,
        private readonly TaxRuleService $taxRuleService,
    ) {}

    /**
     * Get a paginated list of tax classes.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexClasses(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxClass::class);

        $filters = $request->validate([
            'search'    => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:active,inactive'],
        ]);

        $classes = $this->taxService->paginateClasses(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($classes, TaxClassResource::collection($classes), 'Tax classes retrieved successfully.');
    }

    /**
     * Create a new tax class.
     *
     * @param  StoreTaxClassRequest  $request
     * @return JsonResponse
     */
    public function storeClass(StoreTaxClassRequest $request): JsonResponse
    {
        $this->authorize('create', TaxClass::class);

        $taxClass = $this->taxService->createClass($request->validated());

        return $this->created(
            new TaxClassResource($taxClass),
            'Tax class created successfully.',
        );
    }

    /**
     * Get a single tax class.
     *
     * @param  TaxClass  $taxClass
     * @return JsonResponse
     */
    public function showClass(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('view', $taxClass);

        return $this->success(
            new TaxClassResource($this->taxService->findClass($taxClass->id)),
            'Tax class retrieved successfully.',
        );
    }

    /**
     * Update an existing tax class.
     *
     * @param  UpdateTaxClassRequest  $request
     * @param  TaxClass  $taxClass
     * @return JsonResponse
     */
    public function updateClass(UpdateTaxClassRequest $request, TaxClass $taxClass): JsonResponse
    {
        $this->authorize('update', $taxClass);

        $taxClass = $this->taxService->updateClass($taxClass, $request->validated());

        return $this->updated(
            new TaxClassResource($taxClass),
            'Tax class updated successfully.',
        );
    }

    /**
     * Delete a tax class.
     *
     * @param  TaxClass  $taxClass
     * @return JsonResponse
     */
    public function destroyClass(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('delete', $taxClass);

        $this->taxService->deleteClass($taxClass);

        return $this->deleted('Tax class deleted successfully.');
    }

    /**
     * Create a new tax rate.
     *
     * @param StoreTaxRateRequest $request
     * @param TaxClass $taxClass
     * @return JsonResponse
     * @throws Throwable
     */
    public function storeRate(StoreTaxRateRequest $request, TaxClass $taxClass): JsonResponse
    {
        $this->authorize('update', $taxClass);

        $rate = $this->taxRateService->create($taxClass, $request->validated());

        return $this->created(
            new TaxRateResource($rate),
            'Tax rate created successfully.',
        );
    }

    /**
     * Update an existing tax rate.
     *
     * @param StoreTaxRateRequest $request
     * @param TaxRate $taxRate
     * @return JsonResponse
     * @throws Throwable
     */
    public function updateRate(StoreTaxRateRequest $request, TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate->taxClass);

        $rate = $this->taxRateService->update($taxRate, $request->validated());

        return $this->updated(
            new TaxRateResource($rate),
            'Tax rate updated successfully.',
        );
    }

    /**
     * Delete a tax rate.
     *
     * @param TaxRate $taxRate
     * @return JsonResponse
     * @throws Throwable
     */
    public function destroyRate(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate->taxClass);

        $this->taxRateService->delete($taxRate);

        return $this->deleted('Tax rate deleted successfully.');
    }

    /**
     * Create a new tax rule.
     *
     * @param StoreTaxRuleRequest $request
     * @param TaxRate $taxRate
     * @return JsonResponse
     * @throws Throwable
     */
    public function storeRule(StoreTaxRuleRequest $request, TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate->taxClass);

        $rule = $this->taxRuleService->create($taxRate, $request->validated());

        return $this->created(
            new TaxRuleResource($rule->load('taxRegion')),
            'Tax rule created successfully.',
        );
    }

    /**
     * Create a new tax region.
     *
     * @param StoreTaxRegionRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function storeRegion(StoreTaxRegionRequest $request): JsonResponse
    {
        $this->authorize('create', TaxClass::class);

        $region = $this->taxRuleService->createRegion($request->validated());

        return $this->created(
            new TaxRegionResource($region),
            'Tax region created successfully.',
        );
    }

    /**
     * Calculate tax for a given amount and region.
     *
     * @param  CalculateTaxRequest  $request
     * @return JsonResponse
     */
    public function calculate(CalculateTaxRequest $request): JsonResponse
    {
        Gate::authorize('tax.calculate');

        $result = $this->taxService->calculate(
            (float) $request->validated('amount'),
            $request->safe()->only(['country_code', 'state_code', 'postal_code']),
            $request->integer('tax_class_id') ?: null,
        );

        return $this->success($result, 'Tax calculated successfully.');
    }
}
