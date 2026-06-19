<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Central\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds default platform subscription plans.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'For new stores launching their first flash sales.',
                'price' => 29.00,
                'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
                'paddle_price_id' => env('PADDLE_PRICE_STARTER'),
                'paystack_plan_code' => env('PAYSTACK_PLAN_STARTER'),
                'paypal_plan_id' => env('PAYPAL_PLAN_STARTER'),
                'flutterwave_plan_id' => env('FLUTTERWAVE_PLAN_STARTER'),
                'features' => ['1 flash sale / month', '1,000 queue entries', 'Email support'],
                'limits' => ['flash_sales_per_month' => 1, 'queue_entries' => 1000],
                'sort_order' => 1,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'description' => 'For growing brands running frequent drops.',
                'price' => 99.00,
                'stripe_price_id' => env('STRIPE_PRICE_PRO'),
                'paddle_price_id' => env('PADDLE_PRICE_PRO'),
                'paystack_plan_code' => env('PAYSTACK_PLAN_PRO'),
                'paypal_plan_id' => env('PAYPAL_PLAN_PRO'),
                'flutterwave_plan_id' => env('FLUTTERWAVE_PLAN_PRO'),
                'features' => ['Unlimited flash sales', '10,000 queue entries', 'Priority support'],
                'limits' => ['flash_sales_per_month' => null, 'queue_entries' => 10000],
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Custom limits and dedicated support for large teams.',
                'price' => 299.00,
                'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
                'paddle_price_id' => env('PADDLE_PRICE_ENTERPRISE'),
                'paystack_plan_code' => env('PAYSTACK_PLAN_ENTERPRISE'),
                'paypal_plan_id' => env('PAYPAL_PLAN_ENTERPRISE'),
                'flutterwave_plan_id' => env('FLUTTERWAVE_PLAN_ENTERPRISE'),
                'features' => ['Custom limits', 'Dedicated support', 'SLA'],
                'limits' => ['flash_sales_per_month' => null, 'queue_entries' => null],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
