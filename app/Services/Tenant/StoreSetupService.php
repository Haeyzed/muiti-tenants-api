<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\SettingsUpdated;
use App\Models\Tenant\BrandingSetting;
use App\Models\Tenant\BusinessSetting;
use App\Models\Tenant\EmailSetting;
use App\Models\Tenant\InvoiceSetting;
use App\Models\Tenant\NotificationSetting;
use App\Models\Tenant\StoreSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Aggregates and manages all tenant store settings.
 */
class StoreSetupService
{
    /**
     * Get all store settings.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $branding = BrandingSetting::singleton()->load('media');

        return [
            'business' => BusinessSetting::singleton(),
            'store' => StoreSetting::singleton(),
            'branding' => $branding,
            'email' => EmailSetting::singleton(),
            'notifications' => NotificationSetting::singleton(),
            'invoice' => InvoiceSetting::singleton(),
        ];
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
            SettingsUpdated::dispatch('business', $settings->toArray());

            return $settings->fresh();
        });
    }

    /**
     * Update store settings.
     *
     * @param array<string, mixed> $data
     * @return StoreSetting
     * @throws Throwable
     */
    public function updateStore(array $data): StoreSetting
    {
        return DB::transaction(function () use ($data): StoreSetting {
            $settings = StoreSetting::singleton();
            $settings->update($data);
            SettingsUpdated::dispatch('store', $settings->toArray());

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
            SettingsUpdated::dispatch('branding', $settings->toArray());

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
            SettingsUpdated::dispatch('email', $settings->toArray());

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
            SettingsUpdated::dispatch('notifications', $settings->toArray());

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
            SettingsUpdated::dispatch('invoice', $settings->toArray());

            return $settings->fresh();
        });
    }
}
