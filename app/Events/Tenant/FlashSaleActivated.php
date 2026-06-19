<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\FlashSale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a flash sale goes live.
 */
class FlashSaleActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(public FlashSale $flashSale) {}
}
