<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Services\Notifications\Jobs\SendNotificationJob;
use App\Services\Order\OrderValidationService;
use App\Services\Order\OrderCreationService;
use App\Services\Order\OrderFilterService;
use App\Services\PromoCode\PromoCodeValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected OrderValidationService $orderValidationService;
    protected OrderCreationService $orderCreationService;
    protected PromoCodeValidationService $promoValidationService;
    protected OrderFilterService $orderFilterService;

    public function __construct(
        OrderValidationService     $orderValidationService,
        OrderCreationService       $orderCreationService,
        PromoCodeValidationService $promoValidationService,
        OrderFilterService         $orderFilterService
    )
    {
        $this->orderValidationService = $orderValidationService;
        $this->orderCreationService = $orderCreationService;
        $this->promoValidationService = $promoValidationService;
        $this->orderFilterService = $orderFilterService;
    }

    /**
     * Получить список заказов с фильтрацией
     */
    public function index(Request $request): JsonResponse
    {
        // Валидация параметров фильтрации
        $validated = $this->orderFilterService->validateFilterParams($request);

        $user = $request->user();

        $query = Order::with(['items.product', 'items.variant', 'promoCode', 'client.profile']);

        // Если это клиент - показываем только его заказы
        if ($user instanceof \App\Models\Client) {
            $query->where('client_id', $user->id);
        }

        // Применяем фильтры
        $query = $this->orderFilterService->applyFilters($query, $request);

        // Применяем сортировку
        $query = $this->orderFilterService->applySorting($query, $request);

        // Пагинация
        $paginator = $query->paginate($validated['per_page'] ?? 15);

        // Получаем активные фильтры для отображения
        $activeFilters = $this->orderFilterService->getActiveFilters($request);

        $data = [
            'data' => $paginator->items(),
            'meta' => PaginationHelper::format($paginator),
            'filters' => $activeFilters,
        ];

        return $this->successResponse('Список заказов', $data);
    }

    /**
     * Получить детали заказа
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Проверяем права доступа
        if ($user instanceof \App\Models\Client && $order->client_id !== $user->id) {
            return $this->errorResponse('Доступ запрещён', 403);
        }

        $order->load([
            'items.product.images',
            'items.variant.images',
            'client',
            'promoCode'
        ]);

        $summary = $this->orderCreationService->getOrderSummary($order);

        return $this->successResponse('Детали заказа', [
            'order' => $order,
            'summary' => $summary,
        ]);
    }

    /**
     * Создание нового заказа
     */
    public function store(Request $request)
    {
        $validated = $this->validateOrderData($request);

        DB::beginTransaction();

        try {
            // 1. Получаем клиента
            $client = $request->user();

            if (!$client || !($client instanceof \App\Models\Client)) {
                return $this->errorResponse('Клиент не авторизован', 401);
            }

            // 2. Валидируем промокод если указан
            $promoCode = null;
            if (!empty($validated['promo_code'])) {
                $promoResult = $this->validatePromoCode($validated['promo_code'], $client);

                if (!$promoResult['success']) {
                    return $this->errorResponse(
                        $promoResult['message'],
                        422,
                        [
                            'code' => $promoResult['code'] ?? null,
                            'details' => $promoResult
                        ]
                    );
                }

                $promoCode = $promoResult['promo_code'];

                // Логируем успешную валидацию промокода
                $this->promoValidationService->logPromoCodeUsage($promoCode, $client, [
                    'validation_step' => 'order_creation'
                ]);
            }


            // 3. КРИТИЧЕСКАЯ ПРОВЕРКА: Валидируем позиции заказа
            // Проверяем цены, остатки, активность товаров и применимость промокода
            $itemsValidation = $this->orderValidationService->validateOrderItems(
                $validated['items'],
                $promoCode
            );

            if (!$itemsValidation['valid']) {
                DB::rollBack();
                return $this->validationErrorResponse($itemsValidation['errors']);
            }


            // 4. Создаем заказ
            $order = $this->orderCreationService->createOrder($validated, $client->id);


            // 5. Создаем позиции заказа с проверенными ценами
            $totals = $this->orderCreationService->createOrderItems(
                $order,
                $itemsValidation['validated_items']
            );


            // 6. Применяем промокод (если есть) и обновляем суммы
            if ($promoCode) {
                $this->orderCreationService->applyPromoCodeToOrder(
                    $order,
                    $promoCode,
                    $totals['order_total'],
                    $totals['total_discount'],
                    $totals['total_promo_discount']
                );
            } else {
                $this->orderCreationService->updateOrderTotals($order, $totals);
            }

            // 7. Отправляем уведомления
            $this->sendNotifications($client, $order);

            DB::commit();

            // Загружаем заказ со всеми связями для ответа
            $order->load(['items.product', 'items.variant', 'promoCode']);

            return $this->successResponse(
                'Заказ успешно создан',
                [
                    'order' => $order,
                    'summary' => $this->orderCreationService->getOrderSummary($order)
                ],
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Ошибка при создании заказа. Пожалуйста, попробуйте позже.',
                500,
                [
                    'error_details' => config('app.debug') ? $e->getMessage() : null
                ]
            );
        }
    }

    public function getUserOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Пользователь не авторизован'], 401);
        }

        $client = Client::where('id', $user->id)->whereNull('deleted_at')->first();

        if (!$client) {
            return response()->json(['error' => 'Клиент не найден!'], 404);
        }

        $perPage = $request->query('per_page', 10);

        $orders = Order::with([
            'items.product',
            'items.variant',
            'items.color' => function ($sql) {
                $sql->select(['id', 'name', 'code']);
            },
            'deliveryMethod',
            'deliveryTarget',
        ])
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'orders' => $orders->items(), // только список заказов
            'pagination' => PaginationHelper::format($orders)
        ]);
    }


    /**
     * Обновление статуса заказа
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,confirmed,shipped,delivered,cancelled',
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $status = $validated['status'];

            if ($status === 'cancelled') {
                $success = $this->orderCreationService->cancelOrder(
                    $order,
                    $validated['reason'] ?? null
                );
            } elseif ($status === 'confirmed') {
                $success = $this->orderCreationService->confirmOrder($order);
            } else {
                $success = $this->orderCreationService->updateDeliveryStatus($order, $status);
            }

            if (!$success) {
                DB::rollBack();
                return $this->errorResponse('Не удалось обновить статус заказа', 500);
            }

            DB::commit();

            return $this->successResponse(
                'Статус заказа обновлён',
                ['order' => $order->fresh()]
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update order status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Ошибка при обновлении статуса', 500);
        }
    }

    /**
     * Отмена заказа
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Проверяем права доступа
        if ($user instanceof \App\Models\Client && $order->client_id !== $user->id) {
            return $this->errorResponse('Доступ запрещён', 403);
        }

        // Проверяем, можно ли отменить заказ
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return $this->errorResponse(
                'Невозможно отменить заказ в текущем статусе',
                422
            );
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $success = $this->orderCreationService->cancelOrder(
                $order,
                $validated['reason'] ?? 'Отменён клиентом'
            );

            if (!$success) {
                DB::rollBack();
                return $this->errorResponse('Не удалось отменить заказ', 500);
            }

            DB::commit();

            return $this->successResponse(
                'Заказ успешно отменён',
                ['order' => $order->fresh()]
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to cancel order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Ошибка при отмене заказа', 500);
        }
    }

    /**
     * Валидация данных заказа
     */
    private function validateOrderData(Request $request): array
    {
        return $request->validate([
            // Адрес доставки
            'country_code' => 'required|string|size:2',
            'city_name' => 'required|string|max:255',
            'delivery_address' => 'required|string|max:500',

            // Заметки
            'notes' => 'nullable|string|max:1000',

            // Промокод
            'promo_code' => 'nullable|string|max:50',

            // Контактная информация
            'user' => 'required|array',
            'user.first_name' => 'required|string|max:255',
            'user.last_name' => 'required|string|max:255',
            'user.phone' => 'required|string|max:20',

            // Товары
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.color_id' => 'nullable|integer|exists:colors,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'required|numeric|min:0|max:9999999',
        ]);
    }

    /**
     * Валидация промокода
     */
    private function validatePromoCode(string $code, $client): array
    {
        return $this->promoValidationService->validate($code, $client);
    }

    /**
     * Отправка уведомлений о заказе
     */
    private function sendNotifications($client, Order $order): void
    {
        try {
            $message = "Ваш заказ #{$order->id} принят! Сумма: {$order->total_amount} руб.";

            // Отправить через все доступные каналы асинхронно
            if ($client->email) {
                SendNotificationJob::dispatch('email', $client->email, $message, ['order_id' => $order->id]);
            }
            if ($client->profile?->telegram_user_id) {
                SendNotificationJob::dispatch('telegram', $client->profile->telegram_user_id, $message, ['order_id' => $order->id]);
            }

            // и т.д. для других каналов

            Log::info('Order notifications queued', ['order_id' => $order->id]);

        } catch (\Exception $e) {
            Log::error('Failed to queue notifications', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Успешный ответ
     */
    private function successResponse(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            ...$data
        ], $status);
    }

    /**
     * Ответ с ошибкой
     */
    private function errorResponse(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            ...$extra
        ], $status);
    }

    /**
     * Ответ с ошибками валидации
     */
    private function validationErrorResponse(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Обнаружены ошибки при проверке товаров в корзине',
            'errors' => $errors,
        ], 422);
    }
}
