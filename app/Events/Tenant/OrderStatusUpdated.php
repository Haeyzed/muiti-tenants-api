<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order status changes.
 */
class OrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
