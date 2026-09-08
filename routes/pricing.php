<?php

use App\Pricing\Controllers\PricingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/api/pricing/calculate', [PricingController::class, 'calculate']);
});
