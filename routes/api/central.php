<?php

declare(strict_types=1);

use App\Http\Controllers\Central\AuthController;
use App\Http\Controllers\Central\BillingController;
use App\Http\Controllers\Central\BillingWebhookController;
use App\Http\Controllers\Central\PlanController;
use App\Http\Controllers\Central\SettingsController;
use App\Http\Controllers\Central\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/central')->group(function (): void {
    Route::post('billing/webhooks/{provider}', [BillingWebhookController::class, 'handle']);

    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('auth/password', [AuthController::class, 'changePassword']);

        Route::get('tenants/statistics', [TenantController::class, 'statistics']);
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate']);
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend']);
        Route::post('tenants/{tenant}/domains', [TenantController::class, 'storeDomain']);
        Route::post('tenants/{tenant}/domains/{domain}/verify', [TenantController::class, 'verifyDomain']);
        Route::apiResource('tenants', TenantController::class);

        Route::get('plans/options', [PlanController::class, 'options']);
        Route::apiResource('plans', PlanController::class);

        Route::get('billing/plans', [BillingController::class, 'plans']);
        Route::get('tenants/{tenant}/subscription', [BillingController::class, 'subscription']);
        Route::post('tenants/{tenant}/subscribe', [BillingController::class, 'subscribe']);
        Route::post('tenants/{tenant}/subscription/cancel', [BillingController::class, 'cancel']);
        Route::post('tenants/{tenant}/subscription/swap', [BillingController::class, 'swap']);
        Route::post('tenants/{tenant}/billing-portal', [BillingController::class, 'portal']);

        Route::get('settings', [SettingsController::class, 'index']);
        Route::put('settings/business', [SettingsController::class, 'updateBusiness']);
        Route::put('settings/store', [SettingsController::class, 'updateStore']);
        Route::post('settings/branding', [SettingsController::class, 'updateBranding']);
        Route::put('settings/email', [SettingsController::class, 'updateEmail']);
        Route::put('settings/notifications', [SettingsController::class, 'updateNotifications']);
        Route::put('settings/invoice', [SettingsController::class, 'updateInvoice']);
    });
});
