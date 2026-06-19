<?php

declare(strict_types=1);

namespace App\MediaLibrary;

/**
 * Build publicly accessible URLs for files stored on tenant-scoped disks.
 *
 * Tenant assets must be served from the tenant hostname via Stancl's
 * `/tenancy/assets/{path}` route — central APP_URL `/storage/...` links 404.
 */
final class TenantMediaUrl
{
    public static function forPath(string $path): string
    {
        $normalizedPath = ltrim($path, '/');
        $relativeUrl = '/tenancy/assets/' . $normalizedPath;

        if (! app()->runningInConsole() && request()->getHost()) {
            return request()->getSchemeAndHttpHost() . $relativeUrl;
        }

        if (tenancy()->initialized) {
            $domain = tenant()->domains()->first()?->domain;

            if ($domain) {
                $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';

                return "{$scheme}://{$domain}{$relativeUrl}";
            }
        }

        return asset($normalizedPath);
    }
}
