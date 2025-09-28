<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ImageUploadController;
use App\Models\Product;
use App\Http\Controllers\DiscountController;

Route::get('/products', function () {
    return Product::with('upload')->latest()->paginate(20);
});

Route::post('/products/import', [ProductImportController::class, 'import']);
Route::get('/products/import/summary/{id}', [ProductImportController::class, 'summary']);

Route::post('/uploads/init', [ImageUploadController::class, 'init']);
Route::post('/uploads/chunk', [ImageUploadController::class, 'chunk']);
Route::post('/uploads/complete', [ImageUploadController::class, 'complete']);
Route::get('/uploads/status/{upload_id}', [ImageUploadController::class, 'status']);
Route::get('/uploads/{upload_id}/details', [ImageUploadController::class, 'details']);

Route::prefix('discounts')->group(function () {
    Route::post('assign/{user}/{discount}', [DiscountController::class, 'assign']);
    Route::post('revoke/{user}/{discount}', [DiscountController::class, 'revoke']);
    Route::get('apply/{user}', [DiscountController::class, 'apply']);
});
