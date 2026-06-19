<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerAddress;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages customer addresses.
 */
class CustomerAddressService
{
    /**
     * Create a new customer address.
     *
     * @param Customer $customer
     * @param array<string, mixed> $data
     * @return CustomerAddress
     * @throws Throwable
     */
    public function create(Customer $customer, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($customer, $data): CustomerAddress {
            if (!empty($data['is_default'])) {
                $customer->addresses()->update(['is_default' => false]);
            }

            return $customer->addresses()->create($data);
        });
    }

    /**
     * Update an existing customer address.
     *
     * @param CustomerAddress $address
     * @param array<string, mixed> $data
     * @return CustomerAddress
     * @throws Throwable
     */
    public function update(CustomerAddress $address, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($address, $data): CustomerAddress {
            if (!empty($data['is_default'])) {
                $address->customer->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($data);

            return $address->fresh();
        });
    }

    /**
     * Delete a customer address.
     *
     * @param CustomerAddress $address
     * @return void
     */
    public function delete(CustomerAddress $address): void
    {
        $address->delete();
    }

    /**
     * Set a customer address as default.
     *
     * @param CustomerAddress $address
     * @return CustomerAddress
     * @throws Throwable
     */
    public function setDefault(CustomerAddress $address): CustomerAddress
    {
        return $this->update($address, ['is_default' => true]);
    }
}
