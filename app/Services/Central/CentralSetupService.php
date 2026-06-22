<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Models\Central\BrandingSetting;
use App\Models\Central\BusinessSetting;
use App\Models\Central\EmailSetting;
use App\Models\Central\InvoiceSetting;
use App\Models\Central\NotificationSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Aggregates and manages all central store settings.
 */
class CentralSetupService
{
    /**
     * Get all store settings.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return Cache::rememberForever('central_settings', function () {
            $branding = BrandingSetting::singleton()->load('media');

            return [
                'business' => BusinessSetting::singleton(),
                'branding' => $branding,
                'email' => EmailSetting::singleton(),
                'notifications' => NotificationSetting::singleton(),
                'invoice' => InvoiceSetting::singleton(),
            ];
        });
    }

    /**
     * Update business settings.
     *
     * @param array<string, mixed> $data
     * @return BusinessSetting
     * @throws Throwable
     */
    public function updateBusiness(array $data): BusinessSetting
    {
        return DB::transaction(function () use ($data): BusinessSetting {
            $settings = BusinessSetting::singleton();
            $settings->update($data);
            Cache::forget('central_settings');

            return $settings->fresh();
        });
    }

    /**
     * Update branding settings.
     *
     * @param array<string, mixed> $data
     * @param array<string, UploadedFile|null> $media
     * @return BrandingSetting
     * @throws Throwable
     */
    public function updateBranding(array $data, array $media = []): BrandingSetting
    {
        return DB::transaction(function () use ($data, $media): BrandingSetting {
            $settings = BrandingSetting::singleton();
            $settings->update($data);

            foreach (['store_logo', 'store_banner', 'favicon'] as $collection) {
                if (!empty($media[$collection])) {
                    $settings->clearMediaCollection($collection);
                    $settings->addMedia($media[$collection])->toMediaCollection($collection);
                }
            }

            $settings = $settings->fresh(['media']);
            Cache::forget('central_settings');

            return $settings;
        });
    }

    /**
     * Update email settings.
     *
     * @param array<string, mixed> $data
     * @return EmailSetting
     * @throws Throwable
     */
    public function updateEmail(array $data): EmailSetting
    {
        return DB::transaction(function () use ($data): EmailSetting {
            $settings = EmailSetting::singleton();
            $settings->update($data);
            Cache::forget('central_settings');

            return $settings->fresh();
        });
    }

    /**
     * Update notification settings.
     *
     * @param array<string, mixed> $data
     * @return NotificationSetting
     * @throws Throwable
     */
    public function updateNotifications(array $data): NotificationSetting
    {
        return DB::transaction(function () use ($data): NotificationSetting {
            $settings = NotificationSetting::singleton();
            $settings->update($data);
            Cache::forget('central_settings');

            return $settings->fresh();
        });
    }

    /**
     * Update invoice settings.
     *
     * @param array<string, mixed> $data
     * @return InvoiceSetting
     * @throws Throwable
     */
    public function updateInvoice(array $data): InvoiceSetting
    {
        return DB::transaction(function () use ($data): InvoiceSetting {
            $settings = InvoiceSetting::singleton();
            $settings->update($data);
            Cache::forget('central_settings');

            return $settings->fresh();
        });
    }
}
