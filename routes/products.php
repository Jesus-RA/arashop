<?php

use App\Products\Controllers\ProductController;
use App\Products\Controllers\ProductVariantController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/api/products', [ProductController::class, 'index']);
    Route::post('/api/products', [ProductController::class, 'store']);
    Route::post('/api/products/images', [ProductController::class, 'uploadImage']);
    Route::post('/api/products/import/preview', [ProductController::class, 'importPreview']);
    Route::post('/api/products/import', [ProductController::class, 'import']);
    Route::get('/api/products/{product}', [ProductController::class, 'show']);
    Route::put('/api/products/{product}', [ProductController::class, 'update']);
    Route::delete('/api/products/{product}', [ProductController::class, 'destroy']);

    Route::post('/api/products/{product}/variants', [ProductVariantController::class, 'store']);
    Route::put('/api/products/{product}/variants/{variant}', [ProductVariantController::class, 'update']);
    Route::delete('/api/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);
});
