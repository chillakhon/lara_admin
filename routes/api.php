<?php

use App\Http\Controllers\Api\Admin\CartAnalyticsController;
use App\Http\Controllers\Api\Admin\CartController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CDEKController;
use App\Http\Controllers\Api\Admin\ChatsIntegrationController;
use App\Http\Controllers\Api\Admin\ClientController;
use App\Http\Controllers\Api\Admin\ColorController;
use App\Http\Controllers\Api\Admin\ContactRequestController;
use App\Http\Controllers\Api\Admin\ConversationController;
use App\Http\Controllers\Api\Admin\CountriesController;
use App\Http\Controllers\Api\Admin\DeliveryCountryController;
use App\Http\Controllers\Api\Admin\DeliveryMethodController;
use App\Http\Controllers\Api\Admin\DeliveryRateController;
use App\Http\Controllers\Api\Admin\DeliveryZoneController;
use App\Http\Controllers\Api\Admin\DiscountAnalyticsController;
use App\Http\Controllers\Api\Admin\DiscountController;
use App\Http\Controllers\Api\Admin\FavoriteController;
use App\Http\Controllers\Api\Admin\FinancialAnalyticsController;
use App\Http\Controllers\Api\Admin\GiftCard\GiftCardController;
use App\Http\Controllers\Api\Admin\MoySkladController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\OrderStatsController;
use App\Http\Controllers\Api\Admin\OtoBanner\OtoBannerAnalyticsController;
use App\Http\Controllers\Api\Admin\OtoBanner\OtoBannerController;
use App\Http\Controllers\Api\Admin\Statuses\StatusController;
use App\Http\Controllers\Api\Admin\Product\ProductAttributeController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ProductImageController;
use App\Http\Controllers\Api\Admin\ProductOrderController;
use App\Http\Controllers\Api\Admin\ProductVariantController;
use App\Http\Controllers\Api\Admin\PromoCodeClientController;
use App\Http\Controllers\Api\Admin\PromoCodeProductController;
use App\Http\Controllers\Api\Admin\PromoCodeUsageController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\Segment\SegmentController;
use App\Http\Controllers\Api\Admin\ShipmentController;
use App\Http\Controllers\Api\Admin\SimpleProductController;
use App\Http\Controllers\Api\Admin\Tag\ClientTagController;
use App\Http\Controllers\Api\Admin\Tag\TagController;
use App\Http\Controllers\Api\Admin\TaskController;
use App\Http\Controllers\Api\Admin\TaskLabelController;
use App\Http\Controllers\Api\Admin\TaskPriorityController;
use App\Http\Controllers\Api\Admin\TaskStatusController;
use App\Http\Controllers\Api\Admin\ThirdPartyIntegrations\Settings\AnalyticsSettingsController;
use App\Http\Controllers\Api\Admin\ThirdPartyIntegrations\Vk\VKWebhookController;
use App\Http\Controllers\Api\Admin\ThirdPartyIntegrations\VKSettingsController;
use App\Http\Controllers\Api\Admin\UnitController;
use App\Http\Controllers\Api\Admin\UserController;
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

use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\Admin\ReviewController;
use App\Http\Controllers\Api\Public\Catalog\CatalogController;
use App\Http\Controllers\Api\Public\Conversation\PublicConversationController;
use App\Http\Controllers\Api\Public\GiftCard\GiftCardPublicController;
use App\Http\Controllers\Api\Public\OtoBanner\PublicOtoBannerController;
use App\Http\Controllers\Api\Public\WhatsApp\WhatsAppWebhookController;
use App\Http\Controllers\Api\Review\ReviewLikeController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\Admin\SlideController;
use Illuminate\Support\Facades\Route;


//auth user
Route::middleware('guest')->group(function () {
//    Route::post('register', [RegisteredUserController::class, 'register']);
    Route::post('login', [AuthenticatedSessionController::class, 'login']);
    Route::post('check-verification', [AuthenticatedSessionController::class, 'check_verification']);
});


Route::prefix("/public")->group(function () {

    Route::get('/settings/analytics', [AnalyticsSettingsController::class, 'getAnalyticsSettings']);

    Route::prefix('oto-banners')->name('public.oto-banners.')->group(function () {

        // Получить активный баннер для устройства
        Route::get('/active', [PublicOtoBannerController::class, 'getActive'])
            ->name('active');

        // Трекинг просмотра баннера
        Route::post('/{otoBanner}/view', [PublicOtoBannerController::class, 'trackView'])
            ->name('view');

        // Отправить форму баннера
        Route::post('/{otoBanner}/submit', [PublicOtoBannerController::class, 'submit'])
            ->name('submit');
    });


    Route::prefix('gift-cards')->name('gift-cards.')->group(function () {
        // Валидация кода карты (проверка баланса)
        Route::post('/validate', [GiftCardPublicController::class, 'validate'])
            ->name('validate');

        // Альтернативный endpoint для проверки баланса
        Route::post('/check-balance', [GiftCardPublicController::class, 'checkBalance'])
            ->name('check-balance');
    });


    Route::prefix('catalog')->name('catalog.')->group(function () {

        // Категории для меню каталога
        Route::get('/menu-categories', [CatalogController::class, 'menuCategories'])
            ->name('menu-categories');

        // Баннеры для главной страницы
        Route::get('/home-banners', [CatalogController::class, 'homeBanners'])
            ->name('home-banners');

        // Товары каталога с фильтрами
        Route::get('/products', [CatalogController::class, 'products'])
            ->name('products');

        Route::get('/products/{product}', [CatalogController::class, 'getProduct'])
            ->name('products');
    });


    Route::prefix('statuses')->name('statuses.')->group(function () {
        Route::get('/', [StatusController::class, 'index'])->name('index');
    });


    Route::post('/vk/webhook', [VKWebhookController::class, 'webhook']);
    Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'webhook']);

    Route::prefix('/conversations')->group(function () {
        Route::get('/client', [PublicConversationController::class, 'getOrCreateForClient']);
        Route::post('/{conversation}/reply', [PublicConversationController::class, 'reply']);
        Route::post('/{conversation}/read', [PublicConversationController::class, 'read']);
    });


    Route::prefix('delivery')->name('delivery.')->group(function () {

        Route::post('/calculate', [DeliveryController::class, 'calculate'])->name('calculate');

        Route::post('/available-methods', [DeliveryController::class, 'getAvailableMethods'])
            ->name('available-methods');

        Route::get('/track/{tracking_number}', [DeliveryController::class, 'track'])
            ->name('track');

    });

});


//client - admin
Route::get('/products', [ProductController::class, 'index']);


Route::get('/admin-user', [AuthenticatedSessionController::class, 'get_admin_user'])
    ->middleware('auth:sanctum');


Route::prefix('/countries')->group(function () {
    Route::get('/', [CountriesController::class, 'countries']);
    Route::get('/regions', [CountriesController::class, 'regions']);
    Route::get('/cities', [CountriesController::class, 'cities']);
});


//Route::get('products/{product}/images-path', [ProductImageController::class, 'index']);
Route::get('/products/{product}/image', [ProductImageController::class, 'getProductImage']);
Route::get('/product/image/{name}', [ProductImageController::class, 'getProductImageByName']);
Route::get('/products/{product}/main-image', [ProductImageController::class, 'getMainProductImage']);


//contact-requests_public
Route::post('/contact-requests', [ContactRequestController::class, 'store']);

//forImages
Route::get('get_slides', [SlideController::class, 'getSlidesForFrontend']);
Route::get('slides/getImage', [SlideController::class, 'getSlideImage']);
Route::get('/users/get-profile/image', [UserController::class, 'getProfileImage']);

//getImagePromoCode
Route::get('promo-code/getImage', [PromoCodeController::class, 'getImage']);


// clients
Route::prefix("/cart-items")->group(function () {
    Route::get('/', [CartController::class, 'cart_items']);
    Route::post('/add-to-cart', [CartController::class, 'add_item_to_cart']);
    Route::post('/add-multiple-items-to-cart', [CartController::class, 'add_multiple_items_to_cart']);
    Route::delete('/cancel', [CartController::class, 'cancel_cart']);
    Route::delete('/remove-item', [CartController::class, 'remove_single_item_from_cart']);
});

//colors
Route::get('/colors', [ColorController::class, 'index']);
Route::get('/colors/used-in-catalog', [ColorController::class, 'get_colors']);

Route::get('reviews/product/{product}', [ReviewController::class, 'productReviews']);

Route::post('/conversations', [ConversationController::class, 'store']);


Route::get('search', [SearchController::class, 'search'])->name('api.search');


Route::prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index']);
    Route::get('/home-random', [ReviewController::class, 'getMainPageReviews']);
    Route::get('/attributes', [ReviewController::class, 'attributes']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::post('{review}/publish', [ReviewController::class, 'publish']);
        Route::post('{review}/unpublish', [ReviewController::class, 'unpublish']);
        Route::post('{review}/respond', [ReviewController::class, 'respond']);
        Route::delete('{review}', [ReviewController::class, 'destroy']);

        Route::post('{review}/like', [ReviewLikeController::class, 'like']);
        Route::delete('{review}/unlike', [ReviewLikeController::class, 'unlike']);

    });

});

Route::middleware('auth:sanctum')->prefix('favorites')->group(function () {
    Route::post('/sync', [FavoriteController::class, 'sync']);
    Route::post('/add', [FavoriteController::class, 'toggle']);
    Route::get('/', [FavoriteController::class, 'favorites']);
});


//admin panel api dashboard
Route::post('/admin-login', [AuthenticatedSessionController::class, 'admin_login']);
Route::post('/admin-register', [RegisteredUserController::class, 'admin_registration']);
Route::get('/client-user', [AuthenticatedSessionController::class, 'get_user'])->middleware('auth:sanctum');

Route::post('forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('reset-password', [NewPasswordController::class, 'store']);


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


Route::middleware(['auth:sanctum'])->group(function () {


    Route::prefix('oto-banners')->name('oto-banners.')->group(function () {

        // CRUD баннеров
        Route::get('/', [OtoBannerController::class, 'index'])
            ->name('index');

        Route::post('/', [OtoBannerController::class, 'store'])
            ->name('store');

        Route::get('/{otoBanner}', [OtoBannerController::class, 'show'])
            ->name('show');

        Route::put('/{otoBanner}', [OtoBannerController::class, 'update'])
            ->name('update');

        Route::delete('/{otoBanner}', [OtoBannerController::class, 'destroy'])
            ->name('destroy');

        // Дополнительные действия с баннером
        Route::post('/{otoBanner}/duplicate', [OtoBannerController::class, 'duplicate'])
            ->name('duplicate');

        Route::post('/{otoBanner}/toggle-status', [OtoBannerController::class, 'toggleStatus'])
            ->name('toggle-status');

        // Заявки по баннеру
        Route::get('/{otoBanner}/submissions', [OtoBannerController::class, 'submissions'])
            ->name('submissions');

        // Все OTO заявки (из всех баннеров)
        Route::get('/submissions/all', [OtoBannerController::class, 'allSubmissions'])
            ->name('submissions.all');

        // Прикрепить менеджера к заявке
        Route::post('/submissions/{submissionId}/attach-manager', [OtoBannerController::class, 'attachManager'])
            ->name('submissions.attach-manager');


        // ============================================
        // АНАЛИТИКА
        // ============================================

        // Сводная аналитика по всем баннерам
        Route::get('/analytics/summary', [OtoBannerAnalyticsController::class, 'summary'])
            ->name('analytics.summary');

        // Аналитика конкретного баннера
        Route::get('/{otoBanner}/analytics', [OtoBannerAnalyticsController::class, 'show'])
            ->name('analytics.show');

        // График по баннеру
        Route::get('/{otoBanner}/analytics/chart', [OtoBannerAnalyticsController::class, 'chart'])
            ->name('analytics.chart');

        // Экспорт аналитики
        Route::get('/{otoBanner}/analytics/export', [OtoBannerAnalyticsController::class, 'export'])
            ->name('analytics.export');
    });


    Route::prefix('gift-cards')->name('gift-cards.')->group(function () {
        Route::get('/', [GiftCardController::class, 'index'])
            ->name('index');

        // Статистика
        Route::get('/statistics', [GiftCardController::class, 'statistics'])
            ->name('statistics');

        // Экспорт в CSV
        Route::get('/export', [GiftCardController::class, 'export'])
            ->name('export');

        // Детали конкретной карты
        Route::get('/{giftCard}', [GiftCardController::class, 'show'])
            ->name('show');

        // Аннулировать карту
        Route::post('/{giftCard}/cancel', [GiftCardController::class, 'cancel'])
            ->name('cancel');

        // Переотправить карту
        Route::post('/{giftCard}/resend', [GiftCardController::class, 'resend'])
            ->name('resend');
    });


    Route::prefix('tags')->group(function () {
        // CRUD для тегов
        Route::get('/', [TagController::class, 'index']);           // Список всех тегов
        Route::post('/', [TagController::class, 'store']);          // Создать тег
        Route::get('/{tag}', [TagController::class, 'show']);       // Получить тег
        Route::put('/{tag}', [TagController::class, 'update']);     // Обновить тег
        Route::delete('/{tag}', [TagController::class, 'destroy']); // Удалить тег

        // Статистика
        Route::get('/statistics/usage', [TagController::class, 'statistics']);
    });

    Route::prefix('clients/{client}/tags')->group(function () {
        // Получить все теги клиента
        Route::get('/', [ClientTagController::class, 'index']);

        // Заменить все теги клиента
        Route::post('/sync', [ClientTagController::class, 'sync']);

        // Добавить один тег к клиенту
        Route::post('/attach', [ClientTagController::class, 'attach']);

        // Удалить тег у клиента
        Route::post('/detach', [ClientTagController::class, 'detach']);
    });


    Route::prefix('segments')->name('segments.')->group(function () {

        // Список сегментов
        Route::get('/', [SegmentController::class, 'index'])->name('index');

        // Создать сегмент
        Route::post('/', [SegmentController::class, 'store'])->name('store');

        // Получить сегмент
        Route::get('/{segment}', [SegmentController::class, 'show'])->name('show');

        // Обновить сегмент
        Route::put('/{segment}', [SegmentController::class, 'update'])->name('update');

        // Удалить сегмент
        Route::delete('/{segment}', [SegmentController::class, 'destroy'])->name('destroy');

        // Переключить активность
        Route::post('/{segment}/toggle-active', [SegmentController::class, 'toggleActive'])->name('toggle-active');

        // Пересчитать клиентов вручную
        Route::post('/{segment}/recalculate', [SegmentController::class, 'recalculate'])->name('recalculate');

        //  Доступные клиенты для добавления (которых НЕТ в сегменте)
        Route::get('/{segment}/available-clients', [SegmentController::class, 'getAvailableClients'])->name('available-clients');

        // Клиенты сегмента
        Route::get('/{segment}/clients', [SegmentController::class, 'getClients'])->name('clients');

        // Добавить клиентов в сегмент
        Route::post('/{segment}/clients', [SegmentController::class, 'attachClients'])->name('attach-clients');

        // Удалить клиентов из сегмента
        Route::delete('/{segment}/clients', [SegmentController::class, 'detachClients'])->name('detach-clients');

        // Прикрепить промокоды к сегменту
        Route::post('/{segment}/promo-codes', [SegmentController::class, 'attachPromoCodes'])->name('attach-promo-codes');

        // Открепить промокоды от сегмента
        Route::delete('/{segment}/promo-codes', [SegmentController::class, 'detachPromoCodes'])->name('detach-promo-codes');

        // Статистика сегмента
        Route::get('/{segment}/statistics', [SegmentController::class, 'statistics'])->name('statistics');

        // Предпросмотр экспорта
        Route::get('/{segment}/export-preview', [SegmentController::class, 'exportPreview'])->name('export-preview');

        // Экспорт в CSV
        Route::get('/{segment}/export', [SegmentController::class, 'export'])->name('export');
    });

    //notification
    Route::get('/notifications/counter', [NotificationController::class, 'counter']);

    //updateUserProfile
    Route::put('users/update-profile/{user}', [UserController::class, 'update_profile']);
    Route::post('/users/update-profile/image', [UserController::class, 'update_profile_image']);

    Route::prefix("/contact-requests")->group(function () {
        Route::get('/', [ContactRequestController::class, 'index']);
        Route::get('/count', [ContactRequestController::class, 'count']);
        Route::get('/{contact_request}', [ContactRequestController::class, 'show']);
        Route::patch('/{contact_request}', [ContactRequestController::class, 'update']);
        Route::delete('/{contact_request}', [ContactRequestController::class, 'destroy']);

        Route::post('{contact_request}/attach-manager', [ContactRequestController::class, 'attachManager'])
            ->name('contact_request.attach-manager');
    });

    Route::prefix('/slides')->group(function () {
        // список слайдов (публично или под auth — см. примечание)
        Route::get('/', [SlideController::class, 'index']);

        // создать слайд (multipart: файл + поля)
        Route::post('/', [SlideController::class, 'store']);

        // получить один слайд
        Route::get('/{slide}', [SlideController::class, 'show']);

        // обновить слайд (PATCH — частичное, PUT — полное)
        Route::patch('/{slide}', [SlideController::class, 'update']);
        Route::put('/{slide}', [SlideController::class, 'update']);

        // удалить
        Route::delete('/{slide}', [SlideController::class, 'destroy']);
    });


    Route::get('/promo-codes/validate', [PromoCodeController::class, 'validate']);


    Route::prefix('/delivery-services')->group(function () {

        Route::prefix('/cdek')->group(function () {
            Route::get('/locations', [CDEKController::class, 'get_cdek_locations']);
            Route::get('/locations-cities', [CDEKController::class, 'get_cdek_cities']);
            Route::get('/locations-regions', [CDEKController::class, 'get_cdek_regions']);
            Route::get('/tariffs', [CDEKController::class, '']);
        });
    });

    Route::prefix('/conversations')->group(function () {

        //for admin_panel
        // Создать новый чат + первое сообщение
        Route::post('/', [ConversationController::class, 'store']);

        // Получить список всех разговоров (чатов)
        Route::get('/', [ConversationController::class, 'index']);

        // Получить подробную информацию о конкретном разговоре по его ID, включая сообщения и участников
        Route::get('/{conversation}', [ConversationController::class, 'show']);

        // Отправить новое сообщение (ответ) в конкретный разговор
        Route::post('/{conversation}/reply', [ConversationController::class, 'reply']);

        // Закрыть разговор (пометить его как завершённый)
        Route::post('/{conversation}/close', [ConversationController::class, 'close']);

        // Назначить ответственного пользователя (оператора) на разговор
        Route::post('/{conversation}/assign', [ConversationController::class, 'assign']);
    });


    Route::group(['prefix' => 'promo-codes'], function () {
        Route::get('/', [PromoCodeController::class, 'index']);
        Route::post('/', [PromoCodeController::class, 'store']);
        Route::put('/{promoCode}', [PromoCodeController::class, 'update']);
        Route::delete('/{promoCode}', [PromoCodeController::class, 'destroy']);
    });

    Route::group(['prefix' => 'promo-code-clients'], function () {

        Route::get('/available-promo-codes', [PromoCodeClientController::class, 'getAvailablePromoCodes']);

        Route::get('', [PromoCodeClientController::class, 'index']);
        // Создать новую связь
        Route::post('', [PromoCodeClientController::class, 'store']);
        // Показать конкретную связь
        Route::get('/{promoCodeClient}', [PromoCodeClientController::class, 'show']);
        // Удалить связь
        Route::delete('/{promoCodeClient}', [PromoCodeClientController::class, 'destroy']);

    });

    Route::group(['prefix' => 'promo-code-products'], function () {
        Route::get('', [PromoCodeProductController::class, 'index']);
        Route::get('/products/{promoCodeId}', [PromoCodeProductController::class, 'getProductsByPromoCode']);
        Route::post('', [PromoCodeProductController::class, 'store']);
        Route::get('/{promoCodeProduct}', [PromoCodeProductController::class, 'show']);
        Route::put('/{promoCodeProduct}', [PromoCodeProductController::class, 'update']);
        Route::delete('/{promoCodeProduct}', [PromoCodeProductController::class, 'destroy']);
    });

    Route::prefix('promo-code-usage')->group(function () {

        // Общий список всех использований
        Route::get('/', [PromoCodeUsageController::class, 'index']);

        // Сводная статистика по всем промокодам
        Route::get('/summary', [PromoCodeUsageController::class, 'getSummaryStatistics']);

        // Статистика по конкретному промокоду
        Route::get('/promo-code/{promoCodeId}/statistics', [PromoCodeUsageController::class, 'getPromoCodeStatistics']);

        // Детальная информация по использованию промокода
        Route::get('/promo-code/{promoCodeId}/details', [PromoCodeUsageController::class, 'getPromoCodeUsageDetails']);

        // Топ клиентов по использованию промокода
        Route::get('/promo-code/{promoCodeId}/top-clients', [PromoCodeUsageController::class, 'getTopClients']);

        // Статистика по периодам
        Route::get('/promo-code/{promoCodeId}/by-period', [PromoCodeUsageController::class, 'getUsageByPeriod']);

        // Экспорт статистики в CSV
        Route::get('/promo-code/{promoCodeId}/export', [PromoCodeUsageController::class, 'exportStatistics']);
    });


    Route::prefix('/carts')->group(function () {
        Route::get('/', [CartController::class, 'carts']);
        Route::get('/analytics', [CartAnalyticsController::class, 'cartAnalytics']);
    });

    // Financial info api
    Route::prefix('/analytics')->group(function () {
        Route::get('/financial-summary-sales', [FinancialAnalyticsController::class, 'financialSummarySales']);
        Route::get('/financial-summary-orders', [FinancialAnalyticsController::class, 'financialSummaryOrders']);
        Route::get('/combined/analytics', [FinancialAnalyticsController::class, 'combined_analytics']);
        Route::get('/discounts/analytics', [DiscountAnalyticsController::class, 'index']);
        Route::get('/report/dashboard', [FinancialAnalyticsController::class, 'report_dashboard']);
        Route::get('/products/income', [FinancialAnalyticsController::class, 'income_by_products']);
        Route::get('/chart', [FinancialAnalyticsController::class, 'weeklyAmount']);
    });

    // Categories
    Route::group(['prefix' => 'categories', 'as' => 'categories.'], function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/products', [CategoryController::class, 'get_products_of_category']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });

    // Units
    Route::group(['prefix' => 'units', 'as' => 'units.'], function () {
        Route::get('/', [UnitController::class, 'index']);
    });

    //        // Products
    Route::group(['prefix' => 'products', 'as' => 'products.'], function () {

//        Route::get('/', [ProductController::class, 'index']);

        Route::post('/bulk-activate', [ProductController::class, 'bulkActivate']);
        Route::post('/bulk-deactivate', [ProductController::class, 'bulkDeactivate']);


        Route::get('/simple', [SimpleProductController::class, 'index']);

        Route::post('/', [ProductController::class, 'store']);

        Route::get('/{product}', [ProductController::class, 'show']);
        Route::post('/update/{product}', [ProductController::class, 'update']);

        // enhances-dev branch
        Route::get('/price/history', [ProductController::class, 'price_history']);
        Route::get('/{product}/warehouse/history', [ProductController::class, 'warehouse_history']);

        //
        Route::delete('/{product}', [ProductController::class, 'destroy']);
        Route::put('/restore/product', [ProductController::class, 'restoreProduct']);
        Route::post('/{product}/components', [ProductController::class, 'addComponent']);
        Route::delete('/{product}/components/{component}', [ProductController::class, 'removeComponent']);
        Route::get('/{product}/calculate-cost', [ProductController::class, 'calculateCost']);

        // variants
        Route::post('/{product}/variants', [ProductVariantController::class, 'store']);
        Route::put('/variants/{variant}', [ProductVariantController::class, 'update']);
        Route::delete('/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);
        Route::post('/{product}/variants/generate', [ProductController::class, 'generateVariants']);


        // images
        Route::post('/{product}/images', [ProductImageController::class, 'store']);
        Route::delete('/{product}/images/{image}/{variant}', [ProductImageController::class, 'destroy']);
        Route::patch('/{product}/images/{image}/{variant}/main', [ProductImageController::class, 'setMain']);

        Route::delete('/{product}/images/{image}', [ProductImageController::class, 'deleteImg']);

        Route::post('/{product}/variants/{variant}/images', [ProductVariantController::class, 'addImages']);
        Route::delete('/{product}/variants/{variant}/images/{image}', [ProductVariantController::class, 'destroyImage']);


        Route::group(['prefix' => 'order', 'as' => 'order.'], function () {
            Route::get('/list', [ProductOrderController::class, 'getOrderedProducts']);
            // Изменить порядок конкретного товара
            Route::post('{product}/order', [ProductOrderController::class, 'updateOrder']);
            // Инициализировать порядок для всех товаров (только один раз!)
            Route::post('/initialize', [ProductOrderController::class, 'initializeOrders']);
        });


        //  маршруты для характеристик
        Route::group(['prefix' => 'attributes', 'as' => 'attributes.'], function () {
            Route::post('{product}/absorbency', [ProductAttributeController::class, 'updateAbsorbency']);
            Route::post('{product}', [ProductAttributeController::class, 'updateAttributes']);

            Route::post('bulk/update', [ProductAttributeController::class, 'bulkUpdateAttributes']);

        });


    });

    Route::group(['prefix' => 'discounts', 'as' => 'discounts.'], function () {
        Route::get('/', [DiscountController::class, 'index']);
        Route::post('/', [DiscountController::class, 'store']);
        Route::put('/{discount}', [DiscountController::class, 'update']);
        Route::delete('/{discount}', [DiscountController::class, 'destroy']);
    });


    Route::group(['prefix' => 'clients',], function () {

        Route::put('/update-profile', [ClientController::class, 'update_profile']);
        Route::put('/update-delivery-address', [ClientController::class, 'update_delivery_address']);


        Route::get('/', [ClientController::class, 'index']);
        Route::get('/{client}', [ClientController::class, 'show'])->name('show');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        //            Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClientController::class, 'update'])->name('update');
        Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');
    });


    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/stats', [OrderStatsController::class, 'stats'])->name('stats');
        Route::get('/user', [OrderController::class, 'getUserOrders']);

        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');

        // Дополнительные действия
        Route::put('/{order}/status', [OrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::post('/{order}/items', [OrderController::class, 'addItems'])->name('add-items');
        Route::delete('/{order}/items/{item}', [OrderController::class, 'removeItem'])->name('remove-item');


        //DeliveryMethodController
        Route::get('/delivery-methods', [DeliveryMethodController::class, 'index']);


    });

    // Маршруты, доступные только администраторам
    // Управление пользователями
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::put('/{user}/update-password', [UserController::class, 'updatePassword']);
        Route::get('/deleted', [UserController::class, 'indexDeleted']);
        Route::get('/{id}/restore', [UserController::class, 'restore']);
        Route::delete('{id}/forceDestroy', [UserController::class, 'forceDestroy']);
    });

    // Управление ролями и разрешениями (только для супер-админа)
    Route::prefix('/roles')->group(function () {
        Route::get('/with-permissions', [RoleController::class, 'index']);
        Route::get('/', [RoleController::class, 'all_roles']);
        Route::get('/permissions', [RoleController::class, 'all_permissions']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{role}', [RoleController::class, 'update']);
    });


//        // Задачи
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::put('/{task}', [TaskController::class, 'update']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);

        Route::post('/{task}/complete', [TaskController::class, 'complete']);

    });


    // Статусы задач
    Route::prefix('task-statuses')->group(function () {
        Route::get('/', [TaskStatusController::class, 'index']);
        Route::post('/', [TaskStatusController::class, 'store']);
        Route::put('/{status}', [TaskStatusController::class, 'update']);
        Route::delete('/{status}', [TaskStatusController::class, 'destroy']);
        Route::post('/reorder', [TaskStatusController::class, 'reorder']);
    });

    // приоритеты задач
    Route::prefix('task-priorities')->group(function () {
        Route::get('/', [TaskPriorityController::class, 'index']);
        Route::post('/', [TaskPriorityController::class, 'store'])->name('store');
        Route::put('/{priority}', [TaskPriorityController::class, 'update'])->name('update');
        Route::delete('/{priority}', [TaskPriorityController::class, 'destroy'])->name('destroy');
    });

    // Метки задач
    Route::prefix('task-labels')->group(function () {
        Route::get('/', [TaskLabelController::class, 'index']);
        Route::post('/', [TaskLabelController::class, 'store']);
        Route::put('/{label}', [TaskLabelController::class, 'update']);
        Route::delete('/{label}', [TaskLabelController::class, 'destroy']);
    });

    // Маршруты для управления доставкой
    Route::prefix('delivery')->name('delivery.')->group(function () {
        // Методы доставки
        Route::get('/methods', [DeliveryMethodController::class, 'index'])->name('methods.index');
        Route::get('/methods/admin', [DeliveryMethodController::class, 'get_all_delivery_methods'])->name('methods.index');
        Route::get('/methods/{method}', [DeliveryMethodController::class, 'show'])->name('methods.show');
        Route::post('/methods', [DeliveryMethodController::class, 'store'])->name('methods.store');
        Route::put('/methods/{method}', [DeliveryMethodController::class, 'update'])->name('methods.update');
        Route::delete('/methods/{method}', [DeliveryMethodController::class, 'destroy'])->name('methods.destroy');

        Route::post('/methods/countries/{method}', [DeliveryCountryController::class, 'assignCountries']);
        //
//            // Зоны доставки
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


    Route::prefix('/third-party-integrations')->group(function () {

        Route::prefix('/settings/')->group(function () {
            Route::get('/analytics', [AnalyticsSettingsController::class, 'getAnalyticsSettings']);
            Route::post('/analytics/yandex-metrika', [AnalyticsSettingsController::class, 'updateYandexMetrika']);
        });


        Route::prefix('/chats')->group(function () {
            Route::post('/telegram', [ChatsIntegrationController::class, 'telegram_integration']);
        });

        Route::prefix('/vk')->group(function () {
            Route::post('/getVKSettings', [VKSettingsController::class, 'getVKSettings']);
            Route::post('configuration', [VKSettingsController::class, 'configuration']);
            Route::post('/test', [VKSettingsController::class, 'test']);

            Route::post('/webhook', [VKWebhookController::class, 'webhook']);
        });

        Route::prefix('/mail')->group(function () {
            Route::post('/configuration', [ChatsIntegrationController::class, 'updateMailSettings']);
            Route::post('/getMailSettings', [ChatsIntegrationController::class, 'getMailSettings']);
            Route::get('/test', [ChatsIntegrationController::class, 'test_mail']);
        });


        Route::prefix('/cdek')->group(function () {
            Route::post('/settings', [CDEKController::class, 'update_cdek_settings']);
        });
        Route::prefix('/moysklad')->group(function () {
            Route::post('/settings', [MoySkladController::class, 'update_moy_sklad_settings']);
            Route::get('/products', [MoySkladController::class, 'get_products']);
            Route::get('/products/variants', [MoySkladController::class, 'get_product_variants']);
            Route::get('/products/stock', [MoySkladController::class, 'get_products_stock']);
            Route::get('/products/sync', [MoySkladController::class, 'sync_products']);
            Route::get('/currencies', [MoySkladController::class, 'get_currencies']);
            Route::get('/priceTypes', [MoySkladController::class, 'get_price_types']);
            Route::get('/units', [MoySkladController::class, 'get_units']);
            Route::get('/characteristics', [MoySkladController::class, 'get_characteristics']);
            //
            Route::get('/report/dashboard', [MoySkladController::class, 'report_dashboard']);
        });
    });
});
