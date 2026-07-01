<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Exports\Central\TenantsExport;
use App\Enums\Central\TenantStatus;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Central\StoreDomainRequest;
use App\Http\Requests\Central\StoreTenantRequest;
use App\Http\Requests\Central\UpdateDomainRequest;
use App\Http\Requests\Central\UpdateTenantRequest;
use App\Http\Resources\Central\DomainResource;
use App\Http\Resources\Central\TenantResource;
use App\Imports\Central\TenantsImport;
use App\Models\Central\CentralUser;
use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use App\Services\Central\DomainService;
use App\Services\Central\ExcelExportService;
use App\Services\Central\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Manages tenant lifecycle on the central platform API.
 */
class TenantController extends ApiController
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly DomainService $domainService,
        private readonly ExcelExportService $excelExportService,
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

    public function updateDomain(UpdateDomainRequest $request, Tenant $tenant, Domain $domain): JsonResponse
    {
        $this->authorize('update', $tenant);

        abort_unless($domain->tenant_id === $tenant->id, 404);

        $domain = $this->domainService->update($domain, $request->validated());

        return $this->updated(
            new DomainResource($domain),
            'Domain updated successfully.',
        );
    }

    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Tenant::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ]);

        $count = $this->tenantService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} tenants deleted successfully.");
    }

    /**
     * Export tenants to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Tenant::class);

        $validated = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['string'],
            'delivery' => ['sometimes', 'in:download,email'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $tenants = $this->tenantService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new TenantsExport($tenants);
        $filename = 'tenants-export.xlsx';

        if (($validated['delivery'] ?? 'download') === 'email') {
            $content = $this->excelExportService->raw($export);
            $recipient = isset($validated['recipient_id'])
                ? CentralUser::query()->findOrFail($validated['recipient_id'])
                : $request->user();

            Mail::raw('Your tenants export is attached.', function ($message) use ($recipient, $content, $filename): void {
                $message->to($recipient->email)
                    ->subject('Tenants Export')
                    ->attachData(
                        $content,
                        $filename,
                        ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                    );
            });

            return $this->success(null, 'Export sent successfully.');
        }

        return $this->excelExportService->download($export, $filename);
    }

    /**
     * Import tenants from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new TenantsImport, $request->file('file'));

        return $this->success(null, 'Tenants imported successfully.');
    }
}
