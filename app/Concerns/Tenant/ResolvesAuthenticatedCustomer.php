<?php

declare(strict_types=1);

namespace App\Concerns\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\TenantUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Resolves the authenticated customer's business profile from a tenant user.
 */
trait ResolvesAuthenticatedCustomer
{
    protected function resolveCustomer(Request $request): Customer
    {
        /** @var TenantUser $user */
        $user = $request->user();

        $customer = $user->customer;

        if ($customer === null) {
            throw new HttpException(403, 'A customer profile is required for this action.');
        }

        return $customer;
    }
}
