<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\ClientController;
use App\Http\Controllers\Api\Admin\ClientLevelController;
use App\Http\Controllers\Api\Admin\MaterialController;
use App\Http\Controllers\Api\Admin\OptionController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ProductVariantController;
use App\Http\Controllers\Api\Admin\ProductionBatchController;
use App\Http\Controllers\Api\Admin\ProductionController;
use App\Http\Controllers\Api\Admin\UnitController;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Api\Auth\NewPasswordController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LeadTypeController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//client
Route::get('/products', [ProductController::class, 'index'])->middleware('auth:sanctum');
//Route::get('/products/{slug}', [ProductController::class, 'show']);

//Route::get('/categories', [CategoryController::class, 'index']);

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

//admin panel api dashboard

//auth user
Route::middleware('guest')->group(function () {
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::post('login', [AuthenticatedSessionController::class, 'login']);
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::post('reset-password', [NewPasswordController::class, 'store']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class);
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1']);
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);
});


Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::middleware(['role:super-admin,admin,manager'])->group(function () {
        // Categories
        Route::group(['prefix' => 'categories', 'as' => 'categories.'], function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{category}', [CategoryController::class, 'update']);
            Route::delete('/{category}', [CategoryController::class, 'destroy']);
        });
        // Units
        Route::group(['prefix' => 'units', 'as' => 'units.'], function () {
            Route::get('/', [UnitController::class, 'index']);
        });

        // Options
        Route::group(['prefix' => 'options', 'as' => 'options.'], function () {
            Route::get('/', [OptionController::class, 'index'])->name('index');
            Route::post('/', [OptionController::class, 'store'])->name('store');
            Route::put('/{option}', [OptionController::class, 'update'])->name('update');
            Route::delete('/{option}', [OptionController::class, 'destroy'])->name('destroy');
        });

        // Materials
        Route::group(['prefix' => 'materials', 'as' => 'materials.'], function () {
            Route::get('/', [MaterialController::class, 'index']);
            Route::get('/{material}', [MaterialController::class, 'show']);
            Route::post('/', [MaterialController::class, 'store']);
            Route::put('/{material}', [MaterialController::class, 'update']);
            Route::delete('/{material}', [MaterialController::class, 'destroy']);
            Route::post('/{material}/add-stock', [MaterialController::class, 'addStock']);
            Route::post('/{material}/remove-stock', [MaterialController::class, 'removeStock']);
        });

//        // Products
        Route::group(['prefix' => 'products', 'as' => 'products.', 'middleware' => ['permission:products.view,products.manage']], function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);

            Route::get('/{product}', [ProductController::class, 'show'])->name('show');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
//            Route::post('/{product}/components', [ProductController::class, 'addComponent'])->name('addComponent');
//            Route::delete('/{product}/components/{component}', [ProductController::class, 'removeComponent'])->name('removeComponent');
//            Route::get('/{product}/calculate-cost', [ProductController::class, 'calculateCost'])->name('calculateCost');
//            Route::post('/{product}/options/attach', [ProductController::class, 'attachOptions'])
//                ->name('options.attach');
//            Route::post('/{product}/variants/bulk-update', [ProductVariantController::class, 'bulkUpdate'])
//                ->name('variants.bulk-update');
//            //Options
//            Route::post('/{product}/options', [ProductController::class, 'storeOption'])
//                ->name('options.store');
//            Route::put('/{product}/options/{option}', [ProductController::class, 'updateOption'])
//                ->name('options.update');
//            Route::delete('/{product}/options/{option}', [ProductController::class, 'destroyOption'])
//                ->name('options.destroy');
//            //variants
            Route::post('/{product}/variants', [ProductVariantController::class, 'store']);
            Route::put('/variants/{variant}', [ProductVariantController::class, 'update']);
            Route::delete('/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);
            Route::post('/{product}/variants/generate', [ProductController::class, 'generateVariants']);
//
//            //images
//            Route::post('/{product}/images', [ProductImageController::class, 'store'])->name('images.store');
//            Route::delete('/{product}/images/{image}/{variant}', [ProductImageController::class, 'destroy'])->name('images.destroy');
//            Route::patch('/{product}/images/{image}/{variant}/main', [ProductImageController::class, 'setMain'])->name('images.setMain');
//
//            Route::post(
//                '/{product}/variants/{variant}/images',
//                [ProductVariantController::class, 'addImages']
//            )->name('variants.images.store');
//
//            Route::delete(
//                '/{product}/variants/{variant}/images/{image}',
//                [ProductVariantController::class, 'destroyImage']
//            )->name('variants.images.destroy');
        });
//
//        // Product Variants
//        Route::prefix('product-variants')->name('product-variants.')->group(function () {
//            Route::get('/{variant}/recipes', [ProductVariantController::class, 'recipes'])
//                ->name('recipes');
//            Route::get('/{variant}/production-history', [ProductVariantController::class, 'productionHistory'])
//                ->name('production-history');
//            Route::get('/{variant}/stock-movements', [ProductVariantController::class, 'stockMovements'])
//                ->name('stock-movements');
//        });
//
//
//        Route::resource('discounts', DiscountController::class);
//        Route::post('discounts/{discount}/attach-products', [DiscountController::class, 'attachProducts'])
//            ->name('discounts.attach-products');
//        Route::post('discounts/{discount}/attach-variants', [DiscountController::class, 'attachVariants'])
//            ->name('discounts.attach-variants');
//
        Route::group(['prefix' => 'recipes', 'as' => 'recipes.'], function () {

            Route::get('/', [RecipeController::class, 'index']);

//            Route::get('/create', [RecipeController::class, 'create'])
//                ->name('create');
//            Route::post('/', [RecipeController::class, 'store'])
//                ->name('store');
//            Route::get('/{recipe}', [RecipeController::class, 'show'])
//                ->name('show');
//            Route::get('/{recipe}/edit', [RecipeController::class, 'edit'])
//                ->name('edit');
//            Route::put('/{recipe}', [RecipeController::class, 'update'])
//                ->name('update');
//            Route::delete('/{recipe}', [RecipeController::class, 'destroy'])
//                ->name('destroy');
//            Route::post('/estimate-cost', [RecipeController::class, 'estimateCost'])
//                ->name('estimate-cost');
//            Route::post('/{recipe}/cost-rates', [RecipeController::class, 'storeCostRates'])
//                ->name('cost-rates.store');
//
//            Route::post('/{recipe}/duplicate', [RecipeController::class, 'duplicate'])->name('duplicate');
//            Route::get('/{recipe}/compare/{otherRecipe}', [RecipeController::class, 'compare'])->name('compare');
        });

        // Cost Categories
//        Route::get('/cost-categories', [CostCategoryController::class, 'index'])
//            ->name('cost-categories.index');
//
        Route::group(['prefix' => 'clients', 'as' => 'clients.'], function () {
            Route::get('/', [ClientController::class, 'index'])->name('index');
            Route::get('/{client}', [ClientController::class, 'show'])->name('show');
            Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');            Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
            Route::put('/{client}', [ClientController::class, 'update'])->name('update');
            Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');
        });
//
//        Route::group(['prefix' => 'orders', 'as' => 'orders.'], function () {
//            Route::get('/', [OrderController::class, 'index'])->name('index');
//            Route::put('/{order}', [OrderController::class, 'update'])->name('update');
//            Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
//        });
//
        Route::group(['prefix' => 'promo-codes', 'as' => 'promo-codes.'], function () {
            Route::get('/', [PromoCodeController::class, 'index'])->name('index');
            Route::post('/', [PromoCodeController::class, 'store'])->name('store');
            Route::put('/{promoCode}', [PromoCodeController::class, 'update'])->name('update');
            Route::delete('/{promoCode}', [PromoCodeController::class, 'destroy'])->name('destroy');
            Route::get('/{promoCode}/usage', [PromoCodeController::class, 'usage'])->name('usage');
        });
//
//        // Инвентарь
//        Route::prefix('inventory')->name('inventory.')->group(function () {
//            Route::get('/', [InventoryController::class, 'index'])->name('index');
//            Route::post('/add', [InventoryController::class, 'addStock'])->name('add');
//            Route::post('/remove', [InventoryController::class, 'removeStock'])->name('remove');
//            Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions');
//
//            Route::get('/component-usage', [InventoryController::class, 'componentUsage'])
//                ->name('component-usage');
//            Route::post('/reserve-components', [InventoryController::class, 'reserveComponents'])
//                ->name('reserve-components');
//            Route::post('/release-reservation/{reservation}', [InventoryController::class, 'releaseReservation'])
//                ->name('release-reservation');
//        });
//
//
//        // Производство
        Route::prefix('production')->name('production.')->group(function () {
            Route::get('/', [ProductionController::class, 'index'])->name('index');
            Route::get('/create/{recipe}', [ProductionController::class, 'create'])->name('create');
            Route::post('/batches', [ProductionBatchController::class, 'store'])->name('store');
            Route::get('/batches/{batch}', [ProductionController::class, 'show'])->name('show');
            Route::post('/batches/{batch}/start', [ProductionController::class, 'start'])->name('start');
            Route::post('/batches/{batch}/complete', [ProductionController::class, 'complete'])->name('complete');
            Route::post('/batches/{batch}/cancel', [ProductionController::class, 'cancel'])->name('cancel');
            Route::post('/batches/{batch}/add-costs', [ProductionController::class, 'addCosts'])->name('addCosts');

            // Статистика и отчеты
            Route::get('/statistics', [ProductionController::class, 'statistics'])->name('statistics');
            Route::get('/pending', [ProductionController::class, 'pending'])->name('pending');
            Route::get('/history', [ProductionController::class, 'history'])->name('history');
        });
//
//
//        // Orders
        Route::prefix('orders')->name('orders.')->middleware(['role:super-admin,admin,manager', 'permission:orders.view,orders.manage'])->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::post('/', [OrderController::class, 'store'])->name('store');
            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::put('/{order}', [OrderController::class, 'update'])->name('update');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');

            // Дополнительные действия с заказами
            Route::post('/{order}/status', [OrderController::class, 'updateStatus'])->name('update-status');
            Route::post('/{order}/items', [OrderController::class, 'addItems'])->name('add-items');
            Route::delete('/{order}/items/{item}', [OrderController::class, 'removeItem'])->name('remove-item');
        });
//
//
//        // Маршруты, доступные только администраторам
//        Route::middleware(['role:super-admin,admin'])->group(function () {
//            // Управление пользователями
//            Route::prefix('users')->name('users.')->group(function () {
//                Route::get('/', [UserController::class, 'index'])
//                    ->middleware('permission:users.view')
//                    ->name('index');
//                Route::post('/', [UserController::class, 'store'])
//                    ->middleware('permission:users.create')
//                    ->name('store');
//                Route::put('/{user}', [UserController::class, 'update'])
//                    ->middleware('permission:users.edit')
//                    ->name('update');
//                Route::delete('/{user}', [UserController::class, 'destroy'])
//                    ->middleware('permission:users.delete')
//                    ->name('destroy');
//            });
//
//            // Управление ролями и разрешениями (только для супер-админа)
//            Route::middleware(['role:super-admin'])->group(function () {
//                Route::resource('roles', RoleController::class);
//                Route::resource('permissions', PermissionController::class);
//                Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
//                    ->name('roles.updatePermissions');
//            });
//        });
//
//        // Content Management Routes
//        Route::prefix('content')->name('content.')->group(function () {
//
//            Route::get('/', [ContentController::class, 'index'])->name('index');
//
//            // Типы полей
//            Route::get('/field-types', [FieldTypeController::class, 'index'])->name('field-types.index');
//            Route::post('/field-types', [FieldTypeController::class, 'store'])->name('field-types.store');
//            Route::put('/field-types/{fieldType}', [FieldTypeController::class, 'update'])->name('field-types.update');
//            Route::delete('/field-types/{fieldType}', [FieldTypeController::class, 'destroy'])->name('field-types.destroy');
//
//            // Группы полей
//            Route::get('/field-groups', [FieldGroupController::class, 'index'])->name('field-groups.index');
//            Route::post('/field-groups', [FieldGroupController::class, 'store'])->name('field-groups.store');
//            Route::put('/field-groups/{fieldGroup}', [FieldGroupController::class, 'update'])->name('field-groups.update');
//            Route::delete('/field-groups/{fieldGroup}', [FieldGroupController::class, 'destroy'])->name('field-groups.destroy');
//
//            // Блоки контента
//            Route::get('/blocks', [ContentBlockController::class, 'index'])->name('blocks.index');
//            Route::post('/blocks', [ContentBlockController::class, 'store'])->name('blocks.store');
//            Route::put('/blocks/{block}', [ContentBlockController::class, 'update'])->name('blocks.update');
//            Route::delete('/blocks/{block}', [ContentBlockController::class, 'destroy'])->name('blocks.destroy');
//
//            // Контент страниц
//            Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
//            Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
//            Route::put('/pages/{pageContent}', [PageController::class, 'update'])->name('pages.update');
//            Route::delete('/pages/{pageContent}', [PageController::class, 'destroy'])->name('pages.destroy');
//
//            // Управление медиафайлами
//            // Route::post('/upload-image', [MediaController::class, 'uploadImage'])->name('upload-image');
//            // Route::post('/upload-gallery', [MediaController::class, 'uploadGallery'])->name('upload-gallery');
//            // Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
//        });
//
        // Для получения всех уровней клиентов
        Route::get('client-levels', [ClientLevelController::class, 'index'])->name('client-levels.index');

        // Для создания нового уровня клиента
        Route::post('client-levels', [ClientLevelController::class, 'store'])->name('client-levels.store');

        // Для получения конкретного уровня клиента по ID

        // Для обновления уровня клиента
        Route::put('client-levels/{clientLevel}', [ClientLevelController::class, 'update'])->name('client-levels.update');

        // Для удаления уровня клиента
        Route::delete('client-levels/{clientLevel}', [ClientLevelController::class, 'destroy'])->name('client-levels.destroy');



        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::post('/leads/create-client', [LeadController::class, 'createClient'])->name('leads.create-client');

//
//        // Задачи
//        Route::prefix('tasks')->name('tasks.')->group(function () {
//            Route::get('/', [TaskController::class, 'index'])->name('index');
//            Route::post('/', [TaskController::class, 'store'])->name('store');
//            Route::put('/{task}', [TaskController::class, 'update'])->name('update');
//            Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
//
//            // Комментарии к задачам
//            Route::post('/{task}/comments', [TaskCommentController::class, 'store'])->name('comments.store');
//            Route::put('/{task}/comments/{comment}', [TaskCommentController::class, 'update'])->name('comments.update');
//            Route::delete('/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');
//
//            // Вложения к задачам
//            Route::post('/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
//            Route::delete('/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
//            Route::get('/{task}/attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('attachments.download');
//        });
//
//        // Статусы задач
//        Route::prefix('task-statuses')->name('task-statuses.')->group(function () {
//            Route::get('/', [TaskStatusController::class, 'index'])->name('index');
//            Route::post('/', [TaskStatusController::class, 'store'])->name('store');
//            Route::put('/{status}', [TaskStatusController::class, 'update'])->name('update');
//            Route::delete('/{status}', [TaskStatusController::class, 'destroy'])->name('destroy');
//            Route::post('/reorder', [TaskStatusController::class, 'reorder'])->name('reorder');
//        });
//
//        // риоритеты задач
//        Route::prefix('task-priorities')->name('task-priorities.')->group(function () {
//            Route::get('/', [TaskPriorityController::class, 'index'])->name('index');
//            Route::post('/', [TaskPriorityController::class, 'store'])->name('store');
//            Route::put('/{priority}', [TaskPriorityController::class, 'update'])->name('update');
//            Route::delete('/{priority}', [TaskPriorityController::class, 'destroy'])->name('destroy');
//        });
//
//        // Метки задач
//        Route::prefix('task-labels')->name('task-labels.')->group(function () {
//            Route::get('/', [TaskLabelController::class, 'index'])->name('index');
//            Route::post('/', [TaskLabelController::class, 'store'])->name('store');
//            Route::put('/{label}', [TaskLabelController::class, 'update'])->name('update');
//            Route::delete('/{label}', [TaskLabelController::class, 'destroy'])->name('destroy');
//        });
//
//        // Маршруты для управления доставкой
//        Route::prefix('delivery')->name('delivery.')->group(function () {
//            // Методы доставки
//            Route::get('/methods', [DeliveryMethodController::class, 'index'])->name('methods.index');
//            Route::get('/methods/{method}', [DeliveryMethodController::class, 'show'])->name('methods.show');
//            Route::post('/methods', [DeliveryMethodController::class, 'store'])->name('methods.store');
//            Route::put('/methods/{method}', [DeliveryMethodController::class, 'update'])->name('methods.update');
//            Route::delete('/methods/{method}', [DeliveryMethodController::class, 'destroy'])->name('methods.destroy');
//
//            // Зоны доставки
//            Route::get('/methods/{method}/zones', [DeliveryZoneController::class, 'index'])->name('zones.index');
//            Route::post('/methods/{method}/zones', [DeliveryZoneController::class, 'store'])->name('zones.store');
//            Route::put('/zones/{zone}', [DeliveryZoneController::class, 'update'])->name('zones.update');
//            Route::delete('/zones/{zone}', [DeliveryZoneController::class, 'destroy'])->name('zones.destroy');
//
//            // Тарифы доставки
//            Route::get('/zones/{zone}/rates', [DeliveryRateController::class, 'index'])->name('rates.index');
//            Route::post('/zones/{zone}/rates', [DeliveryRateController::class, 'store'])->name('rates.store');
//            Route::put('/rates/{rate}', [DeliveryRateController::class, 'update'])->name('rates.update');
//            Route::delete('/rates/{rate}', [DeliveryRateController::class, 'destroy'])->name('rates.destroy');
//
//            // Отправления
//            // Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
//            // Route::put('/shipments/{shipment}', [ShipmentController::class, 'update'])->name('shipments.update');
//            // Route::get('/shipments/{shipment}/print-label', [ShipmentController::class, 'printLabel'])->name('shipments.print-label');
//            // Route::post('/shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');
//        });
//
//        Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
//            Route::get('/general', [SettingsController::class, 'general'])->name('general');
//            Route::post('/general', [SettingsController::class, 'updateGeneral']);
//
//            Route::get('/integrations', [SettingsController::class, 'integrations'])->name('integrations');
//            Route::post('/integrations', [SettingsController::class, 'updateIntegrations']);
//
//            Route::get('/api-keys', [SettingsController::class, 'apiKeys'])->name('api-keys');
//            Route::post('/api-keys', [SettingsController::class, 'updateApiKeys']);
//            Route::delete('/api-keys/{key}', [SettingsController::class, 'deleteApiKey']);
//
//            Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
//            Route::post('/notifications', [SettingsController::class, 'updateNotifications']);
//
//            Route::get('/payment', [SettingsController::class, 'payment'])->name('payment');
//            Route::post('/payment', [SettingsController::class, 'updatePayment'])->name('payment.update');
//
//            Route::get('/delivery', [SettingsController::class, 'delivery'])->name('delivery');
//            Route::post('/delivery', [SettingsController::class, 'updateDelivery'])->name('delivery.update');
//
//            Route::get('/{type}', [SettingsController::class, 'show'])
//                ->middleware('permission:settings.manage')
//                ->name('show');
//            Route::post('/{type}', [SettingsController::class, 'update'])
//                ->middleware('permission:settings.manage')
//                ->name('update');
//        });
//
//        // Conversations routes
//        Route::group(['prefix' => 'conversations', 'as' => 'conversations.'], function () {
//            Route::get('/', [ConversationController::class, 'index'])->name('index');
//            Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');
//            Route::post('/{conversation}/reply', [ConversationController::class, 'reply'])->name('reply');
//            Route::post('/{conversation}/close', [ConversationController::class, 'close'])->name('close');
//            Route::post('/{conversation}/assign', [ConversationController::class, 'assign'])->name('assign');
//        });
    });
});
