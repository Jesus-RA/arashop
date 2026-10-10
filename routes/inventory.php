<?php

use App\Inventory\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/api/stock', [StockController::class, 'index']);
    Route::get('/api/stock/summary', [StockController::class, 'summary']);
    Route::post('/api/inventory/adjust', [StockController::class, 'adjust']);
    Route::get('/api/variants/{variant}/transactions', [StockController::class, 'transactions']);
});
