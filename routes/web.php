<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ColorManagementController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductComponentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductSizeController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoCodeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
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
        Route::post('/materials/{material}/add-stock', [MaterialController::class, 'addStock'])->name('dashboard.materials.add-stock');
        Route::post('/materials/{material}/remove-stock', [MaterialController::class, 'removeStock'])->name('dashboard.materials.remove-stock');

        // Products
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/components', [ProductController::class, 'addComponent'])->name('products.addComponent');
        Route::delete('/products/{product}/components/{component}', [ProductController::class, 'removeComponent'])->name('products.removeComponent');
        Route::get('/products/{product}/calculate-cost', [ProductController::class, 'calculateCost'])->name('products.calculateCost');

        // Product Variants
        Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
        Route::put('/products/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');
        Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');

        Route::get('color-management', [ColorManagementController::class, 'index'])->name('color-management.index');
        Route::post('color-categories', [ColorManagementController::class, 'storeCategory'])->name('color-categories.store');
        Route::put('color-categories/{category}', [ColorManagementController::class, 'updateCategory'])->name('color-categories.update');
        Route::delete('color-categories/{category}', [ColorManagementController::class, 'destroyCategory'])->name('color-categories.destroy');
        Route::post('colors', [ColorManagementController::class, 'storeColor'])->name('colors.store');
        Route::put('colors/{color}', [ColorManagementController::class, 'updateColor'])->name('colors.update');
        Route::delete('colors/{color}', [ColorManagementController::class, 'destroyColor'])->name('colors.destroy');

        Route::post('/products/{product}/color-options', [ProductController::class, 'addColorOption'])->name('products.color-options.store');
        Route::delete('/products/{product}/color-options/{colorOption}', [ProductController::class, 'removeColorOption'])->name('products.color-options.destroy');
        Route::post('/products/{product}/color-options/{colorOption}/colors', [ProductController::class, 'addColorToOption'])->name('products.color-options.colors.store');
        Route::delete('/products/{product}/color-options/{colorOption}/colors/{colorValue}', [ProductController::class, 'removeColorFromOption'])->name('products.color-options.colors.destroy');

        Route::post('/products/{product}/images', [ProductImageController::class, 'store'])->name('product.images.store');
        Route::delete('/products/{product}/images/{image}/{variant}', [ProductImageController::class, 'destroy'])->name('product.images.destroy');
        Route::patch('/products/{product}/images/{image}/{variant}/main', [ProductImageController::class, 'setMain'])->name('product.images.setMain');

        Route::resource('products.sizes', ProductSizeController::class)->only(['store', 'destroy']);
        Route::resource('products.sizes.components', ProductComponentController::class)->only(['store', 'destroy']);


        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('dashboard.clients.destroy');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('/promo-codes', [PromoCodeController::class, 'index'])->name('promo-codes.index');
        Route::post('/promo-codes', [PromoCodeController::class, 'store'])->name('promo-codes.store');
        Route::put('/promo-codes/{promoCode}', [PromoCodeController::class, 'update'])->name('promo-codes.update');
        Route::delete('/promo-codes/{promoCode}', [PromoCodeController::class, 'destroy'])->name('promo-codes.destroy');
        Route::get('/promo-codes/{promoCode}/usage', [PromoCodeController::class, 'usage'])->name('promo-codes.usage');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/add', [InventoryController::class, 'addStock'])->name('inventory.add');
        Route::post('/inventory/remove', [InventoryController::class, 'removeStock'])->name('inventory.remove');
        Route::get('/inventory/transactions', [InventoryController::class, 'transactions'])->name('inventory.transactions');

        // Маршруты, доступные только администраторам
        Route::middleware(['role:admin'])->group(function () {
            // Управление пользователями
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            // Управление ролями
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

            // Управление разрешениями
            Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
            Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
            Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

            // Обновление разрешений для роли
            Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.updatePermissions');
        });
    });


});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
