<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ImageUploadController;

Route::post('/products/import', [ProductImportController::class, 'import']);
Route::get('/products/import/summary', [ProductImportController::class, 'summary']);

Route::post('/uploads/init', [ImageUploadController::class, 'init']);
Route::post('/uploads/chunk', [ImageUploadController::class, 'chunk']);
Route::post('/uploads/complete', [ImageUploadController::class, 'complete']);
Route::get('/uploads/status/{upload_id}', [ImageUploadController::class, 'status']);

