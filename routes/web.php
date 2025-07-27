<?php

use App\Http\Controllers\TelegraphWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Telegram webhook route
Route::post('/telegram/webhook', [TelegraphWebhookController::class, 'handle'])->name('telegram.webhook');
