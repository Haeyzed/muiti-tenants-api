<?php

declare(strict_types=1);

namespace App\MediaLibrary;

use App\MediaLibrary\TenantMediaUrl;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

/**
 * Generates tenant-scoped media URLs when filesystem tenancy is active.
 *
 * Uses the tenant-aware asset() helper instead of the disk URL so files
 * resolve through Stancl's tenant asset route.
 */
class TenantAwareUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        $url = TenantMediaUrl::forPath($this->getPathRelativeToRoot());

        return $this->versionUrl($url);
    }
}
