<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\DeliveryController;
//use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\LeadTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//Route::get('/products', [ProductController::class, 'index2']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);

Route::get('search', [SearchController::class, 'search'])->name('api.search');
Route::post('/promo-codes/validate', [PromoCodeController::class, 'validate'])->name('api.promo-codes.validate');

Route::prefix('orders')->group(function () {
    Route::get('/user', [OrderController::class, 'getUserOrders']);
    Route::post('/', [OrderController::class, 'store']);
});

Route::prefix('leads')->group(function () {
    Route::post('/', [LeadController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('reviews', [ReviewController::class, 'index']);
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::post('/', [ReviewController::class, 'store']);
        Route::get('product/{product}', [ReviewController::class, 'productReviews']);
    });
    // Route::get('/shipments', [ShipmentController::class, 'userShipments'])
    //     ->name('shipments.index');
});

Route::prefix('delivery')->name('delivery.')->group(function () {
    Route::post('/calculate', [DeliveryController::class, 'calculate'])->name('calculate');

    Route::post('/available-methods', [DeliveryController::class, 'getAvailableMethods'])
        ->name('available-methods');

    Route::get('/track/{tracking_number}', [DeliveryController::class, 'track'])
        ->name('track');
});


