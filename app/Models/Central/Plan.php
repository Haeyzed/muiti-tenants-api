<?php

declare(strict_types=1);

namespace App\Models\Central;

use App\Enums\Central\BillingProvider;
use Database\Factories\Central\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform subscription plan offered to tenants.
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'price',
        'currency',
        'interval',
        'stripe_price_id',
        'paddle_price_id',
        'paystack_plan_code',
        'paypal_plan_id',
        'flutterwave_plan_id',
        'features',
        'limits',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'limits' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function priceIdFor(BillingProvider $provider): ?string
    {
        return match ($provider) {
            BillingProvider::Stripe => $this->stripe_price_id,
            BillingProvider::Paddle => $this->paddle_price_id,
            BillingProvider::Paystack => $this->paystack_plan_code,
            BillingProvider::PayPal => $this->paypal_plan_id,
            BillingProvider::Flutterwave => $this->flutterwave_plan_id,
        };
    }
}
