<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\LeadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/search', [SearchController::class, 'search'])->name('api.search');
Route::post('/promo-codes/validate', [PromoCodeController::class, 'validate'])->name('api.promo-codes.validate');

Route::prefix('orders')->group(function () {
    Route::get('/user', [OrderController::class, 'getUserOrders']);
    Route::post('/', [OrderController::class, 'store']);
});

Route::prefix('leads')->group(function () {
    Route::post('/', [LeadController::class, 'store']);
});

