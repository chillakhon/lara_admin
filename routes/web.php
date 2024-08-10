<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorManagementController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductComponentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSizeController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard/Index');
    })->name('dashboard');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        //categories
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Materials
        Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
        Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
        Route::put('/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
        Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

        // Products
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/components', [ProductController::class, 'addComponent'])->name('products.addComponent');
        Route::delete('/products/{product}/components/{component}', [ProductController::class, 'removeComponent'])->name('products.removeComponent');
        Route::get('/products/{product}/calculate-cost', [ProductController::class, 'calculateCost'])->name('products.calculateCost');

        Route::post('/products/{product}/variants', [ProductController::class, 'createVariant'])->name('products.createVariant');
        Route::put('/products/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.updateVariant');
        Route::delete('/products/variants/{variant}', [ProductController::class, 'deleteVariant'])->name('products.deleteVariant');

        Route::get('color-management', [ColorManagementController::class, 'index'])->name('color-management.index');
        Route::post('color-categories', [ColorManagementController::class, 'storeCategory'])->name('color-categories.store');
        Route::put('color-categories/{category}', [ColorManagementController::class, 'updateCategory'])->name('color-categories.update');
        Route::delete('color-categories/{category}', [ColorManagementController::class, 'destroyCategory'])->name('color-categories.destroy');
        Route::post('colors', [ColorManagementController::class, 'storeColor'])->name('colors.store');
        Route::put('colors/{color}', [ColorManagementController::class, 'updateColor'])->name('colors.update');
        Route::delete('colors/{color}', [ColorManagementController::class, 'destroyColor'])->name('colors.destroy');

    });

    Route::resource('products.sizes', ProductSizeController::class)->only(['store', 'destroy']);
    Route::resource('products.sizes.components', ProductComponentController::class)->only(['store', 'destroy']);
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])
        ->name('products.variants.store');
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])
        ->name('products.variants.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
