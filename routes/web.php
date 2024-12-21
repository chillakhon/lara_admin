<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CostCategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductionBatchController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoCodeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\FieldTypeController;
use App\Http\Controllers\FieldGroupController;
use App\Http\Controllers\ContentBlockController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ClientLevelController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\TaskPriorityController;
use App\Http\Controllers\TaskLabelController;
use App\Http\Controllers\InventoryAuditController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\DeliveryMethodController;
use App\Http\Controllers\DeliveryZoneController;
use App\Http\Controllers\DeliveryRateController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test', function () {
    return Inertia::render('Test', [
        'message' => 'If you see this, Inertia is working!'
    ]);
});

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

        // Options
        Route::prefix('options')->name('options.')->group(function () {
            Route::get('/', [OptionController::class, 'index'])->name('index');
            Route::post('/', [OptionController::class, 'store'])->name('store');
            Route::put('/{option}', [OptionController::class, 'update'])->name('update');
            Route::delete('/{option}', [OptionController::class, 'destroy'])->name('destroy');
        });

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
        Route::post('/products/{product}/options/attach', [ProductController::class, 'attachOptions'])
            ->name('products.options.attach');
        Route::post('products/{product}/variants/bulk-update', [ProductVariantController::class, 'bulkUpdate'])
            ->name('products.variants.bulk-update');

        // Product Options
        Route::post('products/{product}/options', [ProductController::class, 'storeOption'])
            ->name('products.options.store');
        Route::put('products/{product}/options/{option}', [ProductController::class, 'updateOption'])
            ->name('products.options.update');
        Route::delete('products/{product}/options/{option}', [ProductController::class, 'destroyOption'])
            ->name('products.options.destroy');

        // Product Variants
        Route::prefix('product-variants')->name('product-variants.')->group(function () {
            Route::get('/{variant}/recipes', [ProductVariantController::class, 'recipes'])
                ->name('recipes');
            Route::get('/{variant}/production-history', [ProductVariantController::class, 'productionHistory'])
                ->name('production-history');
            Route::get('/{variant}/stock-movements', [ProductVariantController::class, 'stockMovements'])
                ->name('stock-movements');
        });

        Route::group(['prefix' => 'recipes', 'as' => 'recipes.'], function () {
            // Список всех рецептов
            Route::get('/', [RecipeController::class, 'index'])
                ->name('index');

            // Форма создания нового рецепта
            Route::get('/create', [RecipeController::class, 'create'])
                ->name('create');

            // Сохранение нового рецепта
            Route::post('/', [RecipeController::class, 'store'])
                ->name('store');

            // Просмотр рецепта
            Route::get('/{recipe}', [RecipeController::class, 'show'])
                ->name('show');

            // Форма редактирования рецепта
            Route::get('/{recipe}/edit', [RecipeController::class, 'edit'])
                ->name('edit');

            // Обновление рецепта
            Route::put('/{recipe}', [RecipeController::class, 'update'])
                ->name('update');

            // Удаление рецепта
            Route::delete('/{recipe}', [RecipeController::class, 'destroy'])
                ->name('destroy');

            // Расчет стоимости производства
            Route::post('/estimate-cost', [RecipeController::class, 'estimateCost'])
                ->name('estimate-cost');


            Route::post('/{recipe}/cost-rates', [RecipeController::class, 'storeCostRates'])
                ->name('cost-rates.store');
        });

        Route::get('/cost-categories', [CostCategoryController::class, 'index'])
            ->name('cost-categories.index');
        Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
        Route::put('/products/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');
        Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');
        Route::post('products/{product}/variants/generate', [ProductController::class, 'generateVariants'])
            ->name('products.variants.generate');

        Route::post('/products/{product}/images', [ProductImageController::class, 'store'])->name('product.images.store');
        Route::delete('/products/{product}/images/{image}/{variant}', [ProductImageController::class, 'destroy'])->name('product.images.destroy');
        Route::patch('/products/{product}/images/{image}/{variant}/main', [ProductImageController::class, 'setMain'])->name('product.images.setMain');

        Route::delete(
            '/products/{product}/variants/{variant}/images/{image}',
            [ProductVariantController::class, 'destroyImage']
        )->name('products.variants.images.destroy');

        Route::post(
            '/products/{product}/variants/{variant}/images',
            [ProductVariantController::class, 'addImages']
        )->name('products.variants.images.store');

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

        // Инвентарь
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::post('/add', [InventoryController::class, 'addStock'])->name('add');
            Route::post('/remove', [InventoryController::class, 'removeStock'])->name('remove');
            Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions');

            Route::get('/component-usage', [InventoryController::class, 'componentUsage'])
                ->name('component-usage');
            Route::post('/reserve-components', [InventoryController::class, 'reserveComponents'])
                ->name('reserve-components');
            Route::post('/release-reservation/{reservation}', [InventoryController::class, 'releaseReservation'])
                ->name('release-reservation');
        });

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/add', [InventoryController::class, 'addStock'])->name('inventory.add');
        Route::post('/inventory/remove', [InventoryController::class, 'removeStock'])->name('inventory.remove');
        Route::get('/inventory/transactions', [InventoryController::class, 'transactions'])->name('inventory.transactions');

        // Рецепты
        Route::prefix('recipes')->name('recipes.')->group(function () {
            Route::get('/', [RecipeController::class, 'index'])->name('index');
            Route::get('/create', [RecipeController::class, 'create'])->name('create');
            Route::post('/', [RecipeController::class, 'store'])->name('store');
            Route::get('/{recipe}', [RecipeController::class, 'show'])->name('show');
            Route::get('/{recipe}/edit', [RecipeController::class, 'edit'])->name('edit');
            Route::put('/{recipe}', [RecipeController::class, 'update'])->name('update');
            Route::delete('/{recipe}', [RecipeController::class, 'destroy'])->name('destroy');
            Route::post('/{recipe}/duplicate', [RecipeController::class, 'duplicate'])->name('duplicate');
            Route::get('/{recipe}/compare/{otherRecipe}', [RecipeController::class, 'compare'])->name('compare');
        });

        // Производство
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


        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
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

        // Content Management Routes
        Route::prefix('content')->name('content.')->group(function () {

            Route::get('/', [ContentController::class, 'index'])->name('index');

            // Типы полей
            Route::get('/field-types', [FieldTypeController::class, 'index'])->name('field-types.index');
            Route::post('/field-types', [FieldTypeController::class, 'store'])->name('field-types.store');
            Route::put('/field-types/{fieldType}', [FieldTypeController::class, 'update'])->name('field-types.update');
            Route::delete('/field-types/{fieldType}', [FieldTypeController::class, 'destroy'])->name('field-types.destroy');

            // Группы полей
            Route::get('/field-groups', [FieldGroupController::class, 'index'])->name('field-groups.index');
            Route::post('/field-groups', [FieldGroupController::class, 'store'])->name('field-groups.store');
            Route::put('/field-groups/{fieldGroup}', [FieldGroupController::class, 'update'])->name('field-groups.update');
            Route::delete('/field-groups/{fieldGroup}', [FieldGroupController::class, 'destroy'])->name('field-groups.destroy');

            // Блоки контента
            Route::get('/blocks', [ContentBlockController::class, 'index'])->name('blocks.index');
            Route::post('/blocks', [ContentBlockController::class, 'store'])->name('blocks.store');
            Route::put('/blocks/{block}', [ContentBlockController::class, 'update'])->name('blocks.update');
            Route::delete('/blocks/{block}', [ContentBlockController::class, 'destroy'])->name('blocks.destroy');

            // Контент страниц
            Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
            Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
            Route::put('/pages/{pageContent}', [PageController::class, 'update'])->name('pages.update');
            Route::delete('/pages/{pageContent}', [PageController::class, 'destroy'])->name('pages.destroy');

            // Управление медиафайлами
            // Route::post('/upload-image', [MediaController::class, 'uploadImage'])->name('upload-image');
            // Route::post('/upload-gallery', [MediaController::class, 'uploadGallery'])->name('upload-gallery');
            // Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
        });

        Route::resource('client-levels', ClientLevelController::class);


        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::post('/leads/create-client', [LeadController::class, 'createClient'])->name('leads.create-client');


        // Задачи
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::post('/', [TaskController::class, 'store'])->name('store');
            Route::put('/{task}', [TaskController::class, 'update'])->name('update');
            Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');

            // Комментарии к задачам
            Route::post('/{task}/comments', [TaskCommentController::class, 'store'])->name('comments.store');
            Route::put('/{task}/comments/{comment}', [TaskCommentController::class, 'update'])->name('comments.update');
            Route::delete('/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');

            // Вложения к задачам
            Route::post('/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
            Route::delete('/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
            Route::get('/{task}/attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('attachments.download');
        });

        // Статусы задач
        Route::prefix('task-statuses')->name('task-statuses.')->group(function () {
            Route::get('/', [TaskStatusController::class, 'index'])->name('index');
            Route::post('/', [TaskStatusController::class, 'store'])->name('store');
            Route::put('/{status}', [TaskStatusController::class, 'update'])->name('update');
            Route::delete('/{status}', [TaskStatusController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [TaskStatusController::class, 'reorder'])->name('reorder');
        });

        // риоритеты задач
        Route::prefix('task-priorities')->name('task-priorities.')->group(function () {
            Route::get('/', [TaskPriorityController::class, 'index'])->name('index');
            Route::post('/', [TaskPriorityController::class, 'store'])->name('store');
            Route::put('/{priority}', [TaskPriorityController::class, 'update'])->name('update');
            Route::delete('/{priority}', [TaskPriorityController::class, 'destroy'])->name('destroy');
        });

        // Метки задач
        Route::prefix('task-labels')->name('task-labels.')->group(function () {
            Route::get('/', [TaskLabelController::class, 'index'])->name('index');
            Route::post('/', [TaskLabelController::class, 'store'])->name('store');
            Route::put('/{label}', [TaskLabelController::class, 'update'])->name('update');
            Route::delete('/{label}', [TaskLabelController::class, 'destroy'])->name('destroy');
        });

        // Маршруты для управления доставкой
        Route::prefix('delivery')->name('delivery.')->group(function () {
            // Методы доставки
            Route::get('/methods', [DeliveryMethodController::class, 'index'])->name('methods.index');
            Route::get('/methods/{method}', [DeliveryMethodController::class, 'show'])->name('methods.show');
            Route::post('/methods', [DeliveryMethodController::class, 'store'])->name('methods.store');
            Route::put('/methods/{method}', [DeliveryMethodController::class, 'update'])->name('methods.update');
            Route::delete('/methods/{method}', [DeliveryMethodController::class, 'destroy'])->name('methods.destroy');

            // Зоны доставки
            Route::get('/methods/{method}/zones', [DeliveryZoneController::class, 'index'])->name('zones.index');
            Route::post('/methods/{method}/zones', [DeliveryZoneController::class, 'store'])->name('zones.store');
            Route::put('/zones/{zone}', [DeliveryZoneController::class, 'update'])->name('zones.update');
            Route::delete('/zones/{zone}', [DeliveryZoneController::class, 'destroy'])->name('zones.destroy');

            // Тарифы доставки
            Route::get('/zones/{zone}/rates', [DeliveryRateController::class, 'index'])->name('rates.index');
            Route::post('/zones/{zone}/rates', [DeliveryRateController::class, 'store'])->name('rates.store');
            Route::put('/rates/{rate}', [DeliveryRateController::class, 'update'])->name('rates.update');
            Route::delete('/rates/{rate}', [DeliveryRateController::class, 'destroy'])->name('rates.destroy');

            // Отправления
            Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
            Route::put('/shipments/{shipment}', [ShipmentController::class, 'update'])->name('shipments.update');
            Route::get('/shipments/{shipment}/print-label', [ShipmentController::class, 'printLabel'])->name('shipments.print-label');
            Route::post('/shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/general', [SettingsController::class, 'general'])->name('general');
            Route::post('/general', [SettingsController::class, 'updateGeneral']);
            
            Route::get('/integrations', [SettingsController::class, 'integrations'])->name('integrations');
            Route::post('/integrations', [SettingsController::class, 'updateIntegrations']);
            
            Route::get('/api-keys', [SettingsController::class, 'apiKeys'])->name('api-keys');
            Route::post('/api-keys', [SettingsController::class, 'updateApiKeys']);
            Route::delete('/api-keys/{key}', [SettingsController::class, 'deleteApiKey']);
            
            Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
            Route::post('/notifications', [SettingsController::class, 'updateNotifications']);
        });

        Route::get('/settings/payment', [SettingsController::class, 'payment'])->name('settings.payment');
        Route::post('/settings/payment', [SettingsController::class, 'updatePayment'])->name('settings.payment.update');

        Route::get('/settings/delivery', [SettingsController::class, 'delivery'])->name('settings.delivery');
        Route::post('/settings/delivery', [SettingsController::class, 'updateDelivery'])->name('settings.delivery.update');
    });
});

Route::middleware('auth')->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('inventory-audits')->name('inventory-audits.')->group(function () {
        Route::get('/', [InventoryAuditController::class, 'index'])->name('index');
        Route::get('/create', [InventoryAuditController::class, 'create'])->name('create');
        Route::post('/', [InventoryAuditController::class, 'store'])->name('store');
        Route::get('/{id}', [InventoryAuditController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');
        Route::post('/{audit}/start', [InventoryAuditController::class, 'start'])->name('start');
        Route::post('/{audit}/complete', [InventoryAuditController::class, 'complete'])->name('complete');
        Route::post('/{audit}/cancel', [InventoryAuditController::class, 'cancel'])->name('cancel');
        Route::post('/items/{item}/quantity', [InventoryAuditController::class, 'updateQuantity'])->name('update-quantity');
    });
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::post('/', [ReviewController::class, 'store'])->name('store');
        Route::put('{review}/verify', [ReviewController::class, 'verify'])->name('verify');
        Route::put('{review}/publish', [ReviewController::class, 'publish'])->name('publish');
        Route::post('{review}/respond', [ReviewController::class, 'respond'])->name('respond');
        Route::delete('{review}', [ReviewController::class, 'destroy'])->name('destroy');
    });
});

// Добавим публичные маршруты для отслеживания
Route::prefix('tracking')->name('tracking.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Tracking/Index');
    })->name('index');
    
    Route::get('/{tracking_number}', [TrackingController::class, 'show'])->name('show');
});

// Платежи
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('checkout/{order}', [PaymentController::class, 'checkout'])->name('checkout');
    Route::get('success', [PaymentController::class, 'success'])->name('success');
    Route::get('cancel', [PaymentController::class, 'cancel'])->name('cancel');
});

// Вебхуки для платежных систем
Route::prefix('webhook')->name('webhook.')->group(function () {
    Route::post('yookassa', [WebhookController::class, 'yookassa'])->name('yookassa');
    Route::post('yandexpay', [WebhookController::class, 'yandexPay'])->name('yandexpay');
    Route::post('cloudpayment', [WebhookController::class, 'cloudPayment'])->name('cloudpayment');
    Route::post('robokassa', [WebhookController::class, 'robokassa'])->name('robokassa');
});

require __DIR__ . '/auth.php';
