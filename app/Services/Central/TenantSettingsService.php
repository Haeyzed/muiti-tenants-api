<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Models\Central\Tenant;
use App\Models\Central\TenantSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Manages tenant-specific configuration settings.
 */
class TenantSettingsService
{
    /**
     * Get all settings for a tenant.
     *
     * @param Tenant $tenant
     * @return Collection<int, TenantSetting>
     */
    public function all(Tenant $tenant): Collection
    {
        return $tenant->settings()->get();
    }

    /**
     * Get a specific setting value for a tenant.
     *
     * @param Tenant $tenant
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(Tenant $tenant, string $key, mixed $default = null): mixed
    {
        $setting = $tenant->settings()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    /**
     * Set multiple settings for a tenant.
     *
     * @param Tenant $tenant
     * @param array<string, mixed> $settings
     * @return void
     */
    public function setMany(Tenant $tenant, array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($tenant, (string)$key, $value);
        }
    }

    /**
     * Set a specific setting for a tenant.
     *
     * @param Tenant $tenant
     * @param string $key
     * @param mixed $value
     * @return Model
     */
    public function set(Tenant $tenant, string $key, mixed $value): Model
    {
        return $tenant->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? $value : ['value' => $value]],
        );
    }

    /**
     * Delete a specific setting for a tenant.
     *
     * @param Tenant $tenant
     * @param string $key
     * @return void
     */
    public function delete(Tenant $tenant, string $key): void
    {
        $tenant->settings()->where('key', $key)->delete();
    }
}
