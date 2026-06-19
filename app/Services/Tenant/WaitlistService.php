<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\WaitlistType;
use App\Events\Tenant\WaitlistJoined;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use App\Models\Tenant\Waitlist;
use App\Models\Tenant\WaitlistSubscriber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Manages product and flash sale waitlists.
 */
class WaitlistService
{
    /**
     * Paginate the waitlists.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Waitlist>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Waitlist::query()
            ->with(['product', 'flashSale'])
            ->withCount('subscribers')
            ->latest();

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Subscribe a customer to a waitlist.
     *
     * @param Product $product
     * @param string $email
     * @param WaitlistType $type
     * @param int|null $flashSaleId
     * @param Customer|null $customer
     * @return WaitlistSubscriber
     */
    public function subscribe(
        Product      $product,
        string       $email,
        WaitlistType $type = WaitlistType::BackInStock,
        ?int         $flashSaleId = null,
        ?Customer    $customer = null,
    ): WaitlistSubscriber
    {
        $waitlist = Waitlist::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'flash_sale_id' => $flashSaleId,
                'type' => $type,
            ],
            ['is_active' => true],
        );

        $subscriber = $waitlist->subscribers()->updateOrCreate(
            ['email' => $email],
            [
                'customer_id' => $customer?->id,
                'status' => 'active',
                'notified_at' => null,
            ],
        );

        WaitlistJoined::dispatch($subscriber);

        return $subscriber->load('waitlist');
    }

    /**
     * Unsubscribe a customer from a waitlist.
     *
     * @param WaitlistSubscriber $subscriber
     * @return void
     */
    public function unsubscribe(WaitlistSubscriber $subscriber): void
    {
        $subscriber->update(['status' => 'cancelled']);
    }

    /**
     * Get active subscribers for a waitlist.
     *
     * @param Waitlist $waitlist
     * @return list<WaitlistSubscriber>
     */
    public function activeSubscribers(Waitlist $waitlist): array
    {
        return $waitlist->subscribers()
            ->where('status', 'active')
            ->get()
            ->all();
    }
}
