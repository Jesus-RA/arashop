<?php

use App\Orders\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/api/orders', [OrderController::class, 'index']);
    Route::post('/api/orders', [OrderController::class, 'store']);
    Route::get('/api/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/api/orders/{order}', [OrderController::class, 'update']);
});
