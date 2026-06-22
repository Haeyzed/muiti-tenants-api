<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Enums\Central\TenantStatus;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Central\StoreDomainRequest;
use App\Http\Requests\Central\StoreTenantRequest;
use App\Http\Requests\Central\UpdateTenantRequest;
use App\Http\Resources\Central\DomainResource;
use App\Http\Resources\Central\TenantResource;
use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use App\Services\Central\DomainService;
use App\Services\Central\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Throwable;

/**
 * Manages tenant lifecycle on the central platform API.
 */
class TenantController extends ApiController
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly DomainService $domainService,
    ) {}

    /**
     * Get a paginated list of tenants.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'status'   => ['nullable', 'array'],
            'status.*' => [new Enum(TenantStatus::class)],
        ]);

        $tenants = $this->tenantService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($tenants, TenantResource::collection($tenants), 'Tenants retrieved successfully.');
    }

    /**
     * Create a new tenant.
     *
     * @param StoreTenantRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);

        $tenant = $this->tenantService->create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        return $this->created(
            new TenantResource($tenant),
            'Tenant created successfully.',
        );
    }

    /**
     * Display a specific tenant.
     *
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        return $this->success(new TenantResource($this->tenantService->find($tenant->id)), 'Tenant retrieved successfully.');
    }

    /**
     * Update an existing tenant.
     *
     * @param  UpdateTenantRequest  $request
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('update', $tenant);

        $tenant = $this->tenantService->update($tenant, $request->validated());

        return $this->updated(
            new TenantResource($tenant),
            'Tenant updated successfully.',
        );
    }

    /**
     * Delete a tenant.
     *
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->authorize('delete', $tenant);

        $this->tenantService->delete($tenant);

        return $this->deleted('Tenant deleted successfully.');
    }

    /**
     * Activate a tenant.
     *
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function activate(Tenant $tenant): JsonResponse
    {
        $this->authorize('activate', $tenant);

        $tenant = $this->tenantService->activate($tenant);

        return $this->updated(
            new TenantResource($tenant),
            'Tenant activated successfully.',
        );
    }

    /**
     * Suspend a tenant.
     *
     * @param  Tenant  $tenant
     * @return JsonResponse
     */
    public function suspend(Tenant $tenant): JsonResponse
    {
        $this->authorize('suspend', $tenant);

        $tenant = $this->tenantService->suspend($tenant);

        return $this->updated(
            new TenantResource($tenant),
            'Tenant suspended successfully.',
        );
    }

    /**
     * Get statistics about tenants.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        return $this->success($this->tenantService->statistics(), 'Tenant statistics retrieved successfully.');
    }

    /**
     * Add a domain to a tenant.
     *
     * @param StoreDomainRequest $request
     * @param Tenant $tenant
     * @return JsonResponse
     * @throws Throwable
     */
    public function storeDomain(StoreDomainRequest $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('update', $tenant);

        $domain = $this->domainService->createCustomDomain(
            $tenant,
            $request->validated('domain'),
            $request->boolean('is_primary'),
        );

        return $this->created(
            new DomainResource($domain),
            'Domain added successfully.',
        );
    }

    /**
     * Verify a domain for a tenant.
     *
     * @param  Tenant  $tenant
     * @param  Domain  $domain
     * @return JsonResponse
     */
    public function verifyDomain(Tenant $tenant, Domain $domain): JsonResponse
    {
        $this->authorize('update', $tenant);

        abort_unless($domain->tenant_id === $tenant->id, 404);

        $domain = $this->domainService->verify($domain);

        return $this->updated(
            new DomainResource($domain),
            'Domain verified successfully.',
        );
    }
}
