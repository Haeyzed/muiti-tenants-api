<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\UpdateBrandingSettingsRequest;
use App\Http\Requests\Tenant\UpdateBusinessSettingsRequest;
use App\Http\Requests\Tenant\UpdateEmailSettingsRequest;
use App\Http\Requests\Tenant\UpdateInvoiceSettingsRequest;
use App\Http\Requests\Tenant\UpdateNotificationSettingsRequest;
use App\Http\Resources\Tenant\BrandingSettingResource;
use App\Http\Resources\Tenant\BusinessSettingResource;
use App\Http\Resources\Tenant\EmailSettingResource;
use App\Http\Resources\Tenant\InvoiceSettingResource;
use App\Http\Resources\Tenant\NotificationSettingResource;
use App\Models\Central\BusinessSetting;
use App\Services\Central\CentralSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Manages central store settings.
 */
class SettingsController extends ApiController
{
    public function __construct(
        private readonly CentralSetupService $centralSetupService,
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

        $settings = $this->centralSetupService->getAll();

        return $this->success([
            'business' => new BusinessSettingResource($settings['business']),
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

        $settings = $this->centralSetupService->updateBusiness($request->validated());

        return $this->success(
            new BusinessSettingResource($settings),
            'Business settings updated.',
        );
    }

    /**
     * Update branding settings..
     *
     * @param UpdateBrandingSettingsRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function updateBranding(UpdateBrandingSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->centralSetupService->updateBranding(
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
     * @param UpdateEmailSettingsRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function updateEmail(UpdateEmailSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->centralSetupService->updateEmail($request->validated());

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

        $settings = $this->centralSetupService->updateNotifications($request->validated());

        return $this->success(
            new NotificationSettingResource($settings),
            'Notification settings updated.',
        );
    }

    /**
     * Update invoice settings.
     *
     * @param UpdateInvoiceSettingsRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function updateInvoice(UpdateInvoiceSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', BusinessSetting::singleton());

        $settings = $this->centralSetupService->updateInvoice($request->validated());

        return $this->success(
            new InvoiceSettingResource($settings),
            'Invoice settings updated.',
        );
    }
}
