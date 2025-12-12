<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddOrderItemsRequest;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\Client;
use App\Models\Order;
use App\Services\Notifications\Jobs\SendNotificationJob;
use App\Services\Order\OrderAuthorizationService;
use App\Services\Order\OrderDeletionService;
use App\Services\Order\OrderItemService;
use App\Services\Order\OrderUpdateService;
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

    public function __construct(
        protected OrderValidationService     $orderValidationService,
        protected OrderCreationService       $orderCreationService,
        protected OrderUpdateService         $orderUpdateService,
        protected OrderDeletionService       $orderDeletionService,
        protected OrderItemService           $orderItemService,
        protected OrderAuthorizationService  $orderAuthorizationService,
        protected PromoCodeValidationService $promoValidationService,
        protected OrderFilterService         $orderFilterService
    )
    {
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
        if (!$this->orderAuthorizationService->canView($user, $order)) {
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
    public function store(CreateOrderRequest $request)
    {

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
     * Обновление заказа
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (!$this->orderAuthorizationService->canUpdate($user)) {
            return $this->errorResponse('Доступ запрещён', 403);
        }

        if (!$this->orderUpdateService->canUpdate($order)) {
            return $this->errorResponse(
                'Невозможно редактировать заказ в текущем статусе',
                422
            );
        }

        DB::beginTransaction();

        try {
            $success = $this->orderUpdateService->update($order, $request->validated());

            if (!$success) {
                DB::rollBack();
                return $this->errorResponse('Не удалось обновить заказ', 500);
            }

            DB::commit();

            $order->load(['items.product', 'items.variant', 'promoCode', 'client']);

            return $this->successResponse(
                'Заказ успешно обновлён',
                [
                    'order' => $order,
                    'summary' => $this->orderCreationService->getOrderSummary($order)
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Ошибка при обновлении заказа', 500);
        }
    }


    /**
     * Обновление статуса заказа
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        DB::beginTransaction();

        try {
            $status = \App\Enums\OrderStatus::from($request->validated('status'));

            if ($status === \App\Enums\OrderStatus::CANCELLED) {
                $success = $this->orderCreationService->cancelOrder(
                    $order,
                    $request->validated('reason')
                );
            } else {
                $success = $this->orderCreationService->updateOrderStatus($order, $status);
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

        } catch (\ValueError $e) {
            DB::rollBack();
            return $this->errorResponse('Невалидный статус заказа', 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order status', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Ошибка при обновлении статуса', 500);
        }
    }


    /**
     * Отмена заказа
     */
    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (!$this->orderAuthorizationService->canCancel($user, $order)) {
            return $this->errorResponse('Доступ запрещён', 403);
        }

        if (in_array($order->status, [\App\Enums\OrderStatus::DELIVERED, \App\Enums\OrderStatus::CANCELLED])) {
            return $this->errorResponse(
                'Невозможно отменить заказ в текущем статусе',
                422
            );
        }

        DB::beginTransaction();

        try {
            $success = $this->orderCreationService->cancelOrder(
                $order,
                $request->validated('reason') ?? 'Отменён клиентом'
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
            Log::error('Failed to cancel order', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Ошибка при отмене заказа', 500);
        }
    }


    /**
     * Удаление заказа
     */
    public function destroy(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (!$this->orderAuthorizationService->canDelete($user)) {
            return $this->errorResponse('Доступ запрещён', 403);
        }

        if (!$this->orderDeletionService->canDelete($order)) {
            return $this->errorResponse(
                'Невозможно удалить заказ в текущем статусе',
                422
            );
        }

        DB::beginTransaction();

        try {
            $success = $this->orderDeletionService->delete($order);

            if (!$success) {
                DB::rollBack();
                return $this->errorResponse('Не удалось удалить заказ', 500);
            }

            DB::commit();

            return $this->successResponse('Заказ успешно удалён');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete order', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Ошибка при удалении заказа', 500);
        }
    }


    /**
     * Добавить позиции в заказ
     */
    public function addItems(AddOrderItemsRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (!$this->orderAuthorizationService->canUpdate($user)) {
            return $this->errorResponse('Доступ запрещён', 403);
        }

        if (!$this->orderItemService->canModifyItems($order)) {
            return $this->errorResponse(
                'Невозможно добавить товары в заказ с текущим статусом',
                422
            );
        }

        DB::beginTransaction();

        try {
            $result = $this->orderItemService->addItems($order, $request->validated('items'));

            if (!$result) {
                DB::rollBack();
                return $this->errorResponse('Ошибка при добавлении товаров', 500);
            }

            if (!$result['success']) {
                DB::rollBack();
                return $this->validationErrorResponse($result['errors']);
            }

            DB::commit();

            $order->load(['items.product', 'items.variant']);

            return $this->successResponse(
                'Товары успешно добавлены в заказ',
                [
                    'order' => $order,
                    'summary' => $this->orderCreationService->getOrderSummary($order)
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add items', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Ошибка при добавлении товаров', 500);
        }
    }


    /**
     * Удалить позицию из заказа
     */
    public function removeItem(Request $request, Order $order, $itemId): JsonResponse
    {
        $user = $request->user();

        if (!$this->orderAuthorizationService->canUpdate($user)) {
            return $this->errorResponse('Доступ запрещён', 403);
        }

        if (!$this->orderItemService->canModifyItems($order)) {
            return $this->errorResponse(
                'Невозможно удалить товары из заказа с текущим статусом',
                422
            );
        }

        DB::beginTransaction();

        try {
            $success = $this->orderItemService->removeItem($order, $itemId);

            if (!$success) {
                DB::rollBack();
                return $this->errorResponse(
                    'Не удалось удалить товар',
                    422
                );
            }

            DB::commit();

            $order->load(['items.product', 'items.variant']);

            return $this->successResponse(
                'Товар успешно удалён из заказа',
                [
                    'order' => $order,
                    'summary' => $this->orderCreationService->getOrderSummary($order)
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove item', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Ошибка при удалении товара', 500);
        }
    }


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
