<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\UpdateBrandingSettingsRequest;
use App\Http\Requests\Tenant\UpdateBusinessSettingsRequest;
use App\Http\Requests\Tenant\UpdateEmailSettingsRequest;
use App\Http\Requests\Tenant\UpdateInvoiceSettingsRequest;
use App\Http\Requests\Tenant\UpdateNotificationSettingsRequest;
use App\Http\Requests\Tenant\UpdateStoreSettingsRequest;
use App\Http\Resources\Tenant\BrandingSettingResource;
use App\Http\Resources\Tenant\BusinessSettingResource;
use App\Http\Resources\Tenant\EmailSettingResource;
use App\Http\Resources\Tenant\InvoiceSettingResource;
use App\Http\Resources\Tenant\NotificationSettingResource;
use App\Http\Resources\Tenant\StoreSettingResource;
use App\Models\Tenant\BusinessSetting;
use App\Services\Tenant\StoreSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages tenant store settings.
 */
class SettingsController extends ApiController
{
    public function __construct(
        private readonly StoreSetupService $storeSetupService,
    ) {}

    /**
     * Get all store settings.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BusinessSetting::class);

        $settings = $this->storeSetupService->getAll();

        return $this->success([
            'business' => new BusinessSettingResource($settings['business']),
            'store' => new StoreSettingResource($settings['store']),
            'branding' => new BrandingSettingResource($settings['branding']),
            'email' => new EmailSettingResource($settings['email']),
            'notifications' => new NotificationSettingResource($settings['notifications']),
            'invoice' => new InvoiceSettingResource($settings['invoice']),
        ]);
    }

    /**
     * Update business settings.
     *
     * @param  UpdateBusinessSettingsRequest  $request
     * @return JsonResponse
     */
    public function updateBusiness(UpdateBusinessSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->storeSetupService->updateBusiness($request->validated());

        return $this->success(
            new BusinessSettingResource($settings),
            'Business settings updated.',
        );
    }

    /**
     * Update store settings.
     *
     * @param  UpdateStoreSettingsRequest  $request
     * @return JsonResponse
     */
    public function updateStore(UpdateStoreSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->storeSetupService->updateStore($request->validated());

        return $this->success(
            new StoreSettingResource($settings),
            'Store settings updated.',
        );
    }

    /**
     * Update branding settings.
     *
     * @param  UpdateBrandingSettingsRequest  $request
     * @return JsonResponse
     */
    public function updateBranding(UpdateBrandingSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->storeSetupService->updateBranding(
            $request->safe()->except(['store_logo', 'store_banner', 'favicon']),
            [
                'store_logo' => $request->file('store_logo'),
                'store_banner' => $request->file('store_banner'),
                'favicon' => $request->file('favicon'),
            ],
        );

        return $this->success(
            new BrandingSettingResource($settings->load('media')),
            'Branding settings updated.',
        );
    }

    /**
     * Update email settings.
     *
     * @param  UpdateEmailSettingsRequest  $request
     * @return JsonResponse
     */
    public function updateEmail(UpdateEmailSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->storeSetupService->updateEmail($request->validated());

        return $this->success(
            new EmailSettingResource($settings),
            'Email settings updated.',
        );
    }

    /**
     * Update notification settings.
     *
     * @param  UpdateNotificationSettingsRequest  $request
     * @return JsonResponse
     */
    public function updateNotifications(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->storeSetupService->updateNotifications($request->validated());

        return $this->success(
            new NotificationSettingResource($settings),
            'Notification settings updated.',
        );
    }

    /**
     * Update invoice settings.
     *
     * @param  UpdateInvoiceSettingsRequest  $request
     * @return JsonResponse
     */
    public function updateInvoice(UpdateInvoiceSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->storeSetupService->updateInvoice($request->validated());

        return $this->success(
            new InvoiceSettingResource($settings),
            'Invoice settings updated.',
        );
    }
}
