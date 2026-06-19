<?php

use Illuminate\Support\Facades\Route;
use Laravel\Paddle\Http\Controllers\WebhookController as PaddleWebhookController;

Route::get('/', function () {
    return view('welcome');
});

Route::post(config('cashier.paddle_path', 'paddle').'/webhook', [PaddleWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');
