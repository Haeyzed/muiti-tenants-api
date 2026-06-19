<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a product is updated in a tenant store.
 */
class ProductUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Product $product) {}
}
