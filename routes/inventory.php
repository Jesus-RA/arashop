<?php

use App\Inventory\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/api/stock', [StockController::class, 'index']);
    Route::get('/api/stock/{variant}/history', [StockController::class, 'history']);
    Route::post('/api/stock/adjustments', [StockController::class, 'store']);
});
