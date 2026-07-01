<?php

namespace App\Providers;

use App\Events\Tenant\CheckoutSessionAdmitted;
use App\Events\Tenant\FlashSaleActivated;
use App\Events\Tenant\FlashSaleEnded;
use App\Events\Tenant\LeaveRequestSubmitted;
use App\Events\Tenant\OrderPlaced;
use App\Events\Tenant\OrderStatusUpdated;
use App\Events\Tenant\PaymentCompleted;
use App\Events\Tenant\PaymentInitiated;
use App\Events\Tenant\TeamInvitationSent;
use App\Events\Tenant\WaitlistJoined;
use App\Listeners\Tenant\HandleTenantDomainEvents;
use App\Listeners\Tenant\HandleTenantStaffNotifications;
use App\Models\Central\CentralUser;
use App\Models\Central\Plan;
use App\Models\Central\StripeSubscription;
use App\Models\Central\StripeSubscriptionItem;
use App\Models\Central\Tenant;
use App\Models\Tenant\Brand;
use App\Models\Tenant\BusinessSetting;
use App\Models\Tenant\Cart;
use App\Models\Tenant\CartItem;
use App\Models\Tenant\Category;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerGroup;
use App\Models\Tenant\Department;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\OnboardingProgress;
use App\Models\Tenant\Order;
use App\Models\Tenant\Payment;
use App\Models\Tenant\Position;
use App\Models\Tenant\Product;
use App\Models\Tenant\Staff;
use App\Models\Tenant\TaxClass;
use App\Models\Tenant\TeamInvitation;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Waitlist;
use App\Models\Tenant\WaitlistSubscriber;
use App\Policies\Central\CentralUserPolicy;
use App\Policies\Central\PlanPolicy;
use App\Policies\Central\TenantPolicy;
use App\Policies\Tenant\BrandPolicy;
use App\Policies\Tenant\CartPolicy;
use App\Policies\Tenant\CategoryPolicy;
use App\Policies\Tenant\CustomerGroupPolicy;
use App\Policies\Tenant\CustomerPolicy;
use App\Policies\Tenant\DepartmentPolicy;
use App\Policies\Tenant\FlashSalePolicy;
use App\Policies\Tenant\HrPolicy;
use App\Policies\Tenant\OnboardingPolicy;
use App\Policies\Tenant\OrderPolicy;
use App\Policies\Tenant\PaymentPolicy;
use App\Policies\Tenant\PositionPolicy;
use App\Policies\Tenant\ProductPolicy;
use App\Policies\Tenant\SettingsPolicy;
use App\Policies\Tenant\StaffPolicy;
use App\Policies\Tenant\TaxClassPolicy;
use App\Policies\Tenant\TeamInvitationPolicy;
use App\Policies\Tenant\TeamPolicy;
use App\Policies\Tenant\WaitlistPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Paddle\Cashier as PaddleCashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Tenant::class);
        Cashier::useSubscriptionModel(StripeSubscription::class);
        Cashier::useSubscriptionItemModel(StripeSubscriptionItem::class);

        PaddleCashier::ignoreRoutes();

        Event::listen(OrderPlaced::class, [HandleTenantDomainEvents::class, 'handleOrderPlaced']);
        Event::listen(OrderStatusUpdated::class, [HandleTenantDomainEvents::class, 'handleOrderStatusUpdated']);
        Event::listen(PaymentInitiated::class, [HandleTenantDomainEvents::class, 'handlePaymentInitiated']);
        Event::listen(PaymentCompleted::class, [HandleTenantDomainEvents::class, 'handlePaymentCompleted']);
        Event::listen(CheckoutSessionAdmitted::class, [HandleTenantDomainEvents::class, 'handleCheckoutSessionAdmitted']);
        Event::listen(WaitlistJoined::class, [HandleTenantDomainEvents::class, 'handleWaitlistJoined']);

        Event::listen(TeamInvitationSent::class, [HandleTenantStaffNotifications::class, 'handleTeamInvitationSent']);
        Event::listen(LeaveRequestSubmitted::class, [HandleTenantStaffNotifications::class, 'handleLeaveRequestSubmitted']);
        Event::listen(FlashSaleActivated::class, [HandleTenantStaffNotifications::class, 'handleFlashSaleActivated']);
        Event::listen(FlashSaleEnded::class, [HandleTenantStaffNotifications::class, 'handleFlashSaleEnded']);

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Plan::class, PlanPolicy::class);
        Gate::policy(CentralUser::class, CentralUserPolicy::class);
        Gate::policy(OnboardingProgress::class, OnboardingPolicy::class);
        Gate::policy(BusinessSetting::class, SettingsPolicy::class);
        Gate::policy(TenantUser::class, TeamPolicy::class);
        Gate::policy(TeamInvitation::class, TeamInvitationPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerGroup::class, CustomerGroupPolicy::class);
        Gate::policy(Staff::class, StaffPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);
        Gate::policy(TaxClass::class, TaxClassPolicy::class);
        Gate::define('hr.view', [HrPolicy::class, 'view']);
        Gate::define('hr.manage', [HrPolicy::class, 'manage']);
        Gate::define('tax.calculate', fn (TenantUser $user): bool => $user->can('tax.calculate'));
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(FlashSale::class, FlashSalePolicy::class);
        Gate::policy(Waitlist::class, WaitlistPolicy::class);
        Gate::policy(WaitlistSubscriber::class, WaitlistPolicy::class);
        Gate::policy(Cart::class, CartPolicy::class);
        Gate::policy(CartItem::class, CartPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
    }
}
