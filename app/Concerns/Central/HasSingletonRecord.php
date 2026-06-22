<?php

declare(strict_types=1);

namespace App\Concerns\Central;

/**
 * Provides a first-or-create singleton accessor for single-row settings tables.
 */
trait HasSingletonRecord
{
    public static function singleton(): static
    {
        return static::query()->firstOrCreate([]);
    }
}
