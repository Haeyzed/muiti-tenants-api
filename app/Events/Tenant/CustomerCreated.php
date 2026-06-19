<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a new customer is created.
 */
class CustomerCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Customer $customer) {}
}
