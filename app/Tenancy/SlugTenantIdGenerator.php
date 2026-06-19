<?php

declare(strict_types=1);

namespace App\Tenancy;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

/**
 * Generates human-readable slug-based tenant identifiers.
 */
class SlugTenantIdGenerator implements UniqueIdentifierGenerator
{
    public static function generate(mixed $resource): string
    {
        $name = 'tenant';

        if (is_array($resource)) {
            $name = $resource['name'] ?? $name;
        } elseif (is_object($resource) && method_exists($resource, 'getAttribute')) {
            $name = (string) ($resource->getAttribute('name') ?? $name);
        }

        return Str::slug($name).'-'.Str::lower(Str::random(6));
    }
}
