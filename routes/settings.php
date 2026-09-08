<?php

use App\Settings\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/api/settings', [SettingsController::class, 'index']);
    Route::put('/api/settings', [SettingsController::class, 'update']);
});
