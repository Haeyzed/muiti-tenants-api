<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'customer_group_id' => $this->customer_group_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'loyalty_points' => $this->loyalty_points,
            'total_spent' => $this->total_spent,
            'orders_count' => $this->orders_count,
            'is_active' => $this->is_active,
            'group' => $this->whenLoaded('group', fn () => new CustomerGroupResource($this->group)),
            'tags' => $this->whenLoaded('tags', fn () => CustomerTagResource::collection($this->tags)),
            'addresses' => $this->whenLoaded('addresses', fn () => CustomerAddressResource::collection($this->addresses)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
