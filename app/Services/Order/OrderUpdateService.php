<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\PromoCode;
use App\Services\PromoCode\PromoCodeValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderUpdateService
{
    public function __construct(
        protected OrderHistoryService $historyService,
        protected PromoCodeValidationService $promoValidationService,
        protected OrderValidationService $orderValidationService
    ) {}

    /**
     * Обновить данные заказа
     */
    public function update(Order $order, array $data): bool
    {
        try {
            // Снимок отслеживаемых полей для diff'а истории
            $originalSnapshot = [
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'total_amount' => $order->total_amount,
                'delivery_method_id' => $order->delivery_method_id,
                'delivery_cost' => $order->delivery_cost,
                'notes' => $order->notes,
            ];
            // Поля напрямую обновляемые в таблице orders
            $allowedFields = [
                'notes',
                'client_id',
                'status',
                'payment_status',
                'payment_method',
                'source',
                'delivery_method_id',
                'delivery_date',
                'delivery_comment',
            ];

            $filteredData = array_intersect_key(
                array_filter($data, fn ($value) => $value !== null && $value !== ''),
                array_flip($allowedFields)
            );

            // Определяем delivery_method_id по имени если пришёл объект delivery_method
            if (! isset($filteredData['delivery_method_id']) && isset($data['delivery_method']['name'])) {
                $method = DeliveryMethod::where('name', $data['delivery_method']['name'])->first();
                if ($method) {
                    $filteredData['delivery_method_id'] = $method->id;
                }
            }

            // Обновляем контактные данные клиента если переданы
            if (isset($data['user']) && is_array($data['user'])) {
                $this->updateClientContactInfo($order, $data['user']);
            }

            // Обработка адреса доставки и/или получателя.
            // Получатель хранится в той же таблице order_addresses (см. migration),
            // поэтому пишем единым updateOrCreate, чтобы не плодить лишние строки.
            $hasAddress = isset($data['delivery_address']) && is_array($data['delivery_address']);
            $hasRecipient = isset($data['recipient']) && is_array($data['recipient']);

            if ($hasAddress) {
                // Если delivery_date есть на верхнем уровне, но нет в delivery_address — добавляем
                if (isset($data['delivery_date']) && ! isset($data['delivery_address']['delivery_date'])) {
                    $data['delivery_address']['delivery_date'] = $data['delivery_date'];
                }

                // Извлекаем delivery_date из delivery_address если есть
                if (array_key_exists('delivery_date', $data['delivery_address'])) {
                    $filteredData['delivery_date'] = $this->formatDeliveryDate($data['delivery_address']['delivery_date']);
                }
            }

            if ($hasAddress || $hasRecipient) {
                $this->updateDeliveryAddress(
                    $order,
                    $data['delivery_address'] ?? [],
                    $data['recipient'] ?? []
                );
            }

            if (isset($data['items'])) {
                $this->updateOrderItems($order, $data['items']);
            }

            // Обработка промокода — привязка/снятие.
            // Сравниваем только если ключ promo_code пришёл в data, чтобы не сбросить
            // купон при PATCH-обновлении других полей.
            if (array_key_exists('promo_code', $data)) {
                $this->applyPromoCodeChange($order, $data['promo_code'] ?: null);
            }

            $order->update($filteredData);

            // Пишем в историю diff отслеживаемых полей
            $order->refresh();
            $updatedSnapshot = [
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'total_amount' => $order->total_amount,
                'delivery_method_id' => $order->delivery_method_id,
                'delivery_cost' => $order->delivery_cost,
                'notes' => $order->notes,
            ];
            $this->historyService->logUpdated($order, $originalSnapshot, $updatedSnapshot);

            Log::info('Order updated', [
                'order_id' => $order->id,
                'updated_fields' => array_keys($filteredData),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Обновить контактную информацию клиента заказа
     */
    private function updateClientContactInfo(Order $order, array $userData): void
    {
        $clientId = $order->client_id;

        if (! $clientId) {
            return;
        }

        $client = Client::find($clientId);

        if (! $client) {
            return;
        }

        $updateData = array_filter([
            'first_name' => $userData['first_name'] ?? null,
            'last_name'  => $userData['last_name'] ?? null,
            'phone'      => $userData['phone'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if (! empty($updateData)) {
            $client->update($updateData);
        }
    }

    /**
     * Форматировать дату доставки
     */
    private function formatDeliveryDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning('Failed to parse delivery_date', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Обновить адрес доставки заказа
     */
    private function updateDeliveryAddress(Order $order, array $addressData, array $recipientData = []): void
    {
        $payload = [];

        // Обновляем только те поля, которые реально пришли в запросе,
        // чтобы пустой recipient не затирал существующего получателя и наоборот.
        if (! empty($addressData)) {
            $payload += [
                'country' => $addressData['country'] ?? null,
                'region' => $addressData['region'] ?? null,
                'city' => $addressData['city'] ?? null,
                'postal_code' => $addressData['postal_code'] ?? null,
                'address' => $addressData['address'] ?? null,
                'entrance' => $addressData['entrance'] ?? null,
                'floor' => $addressData['floor'] ?? null,
                'intercom' => $addressData['intercom'] ?? null,
                'delivery_comment' => $addressData['delivery_comment'] ?? null,
                'delivery_date' => $this->formatDeliveryDate($addressData['delivery_date'] ?? null),
                'buyer_comment' => $addressData['buyer_comment'] ?? null,
            ];
        }

        if (! empty($recipientData)) {
            $payload += [
                'recipient_first_name' => $recipientData['first_name'] ?? null,
                'recipient_last_name' => $recipientData['last_name'] ?? null,
                'recipient_middle_name' => $recipientData['middle_name'] ?? null,
                'recipient_phone' => $recipientData['phone'] ?? null,
            ];
        }

        if (empty($payload)) {
            return;
        }

        $order->address()->updateOrCreate(
            ['order_id' => $order->id],
            $payload
        );
    }

    /**
     * Обновить товары в заказе
     */
    private function updateOrderItems(Order $order, array $items): void
    {
        // Удаляем старые товары
        $order->items()->delete();

        // Создаем новые
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'color_id' => $item['color_id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        // Пересчитываем сумму заказа
        $total = $order->items()->sum(DB::raw('quantity * price'));
        $order->update(['total_amount' => $total]);
    }

    /**
     * Применить/снять промокод к существующему заказу.
     * Вызывается из update() когда в payload пришёл ключ `promo_code`.
     *
     * Логика:
     * - Если код пустой и был привязан — снимаем (decrement times_used + удаляем usage).
     * - Если код задан и совпадает с текущим — ничего не делаем.
     * - Иначе валидируем новый код и привязываем (заменяя предыдущий, если был).
     */
    private function applyPromoCodeChange(Order $order, ?string $newCode): void
    {
        $currentPromoId = $order->promo_code_id;
        $currentCode = $currentPromoId
            ? optional(PromoCode::find($currentPromoId))->code
            : null;

        $newCode = $newCode !== null ? trim($newCode) : null;
        if ($newCode === '') {
            $newCode = null;
        }

        // Без изменений
        if ($newCode === $currentCode) {
            return;
        }

        // Снятие старого промокода (если был)
        if ($currentPromoId) {
            $oldPromo = PromoCode::find($currentPromoId);
            if ($oldPromo) {
                $oldPromo->usages()
                    ->where('order_id', $order->id)
                    ->delete();
                $oldPromo->decrement('times_used');
            }
            $order->promo_code_id = null;
            $order->total_promo_discount = 0;
            // discount_amount хранит сумму всех скидок: items + promo. После снятия промо
            // оставляем только items-скидку (если была посчитана).
            $order->discount_amount = $order->total_items_discount ?? 0;
        }

        // Если новый код пустой — просто очистка, ничего не применяем
        if ($newCode === null) {
            $order->save();
            $order->updateTotalAmount();
            return;
        }

        // Валидация нового промокода
        $client = $order->client;
        if (! $client) {
            Log::warning('OrderUpdate: client missing, cannot validate promo code', [
                'order_id' => $order->id,
            ]);
            return;
        }

        $validation = $this->promoValidationService->validate($newCode, $client);
        if (! $validation['success']) {
            // Не падаем — просто не применяем. Контроллер для валидации
            // нового кода должен использовать /api/promo-codes/validate.
            Log::info('OrderUpdate: promo code validation failed', [
                'order_id' => $order->id,
                'code' => $newCode,
                'reason' => $validation['code'] ?? 'unknown',
            ]);
            return;
        }

        $promoCode = $validation['promo_code'];

        // Пересчёт промо-скидки по существующим позициям заказа.
        // Используем ту же логику что и при создании (validateOrderItems с promoCode),
        // но без проверки фронт-цен — берём как есть из БД.
        $itemsForValidation = $order->items()
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'variant_id' => $item->product_variant_id,
                    'color_id' => $item->color_id,
                    'quantity' => $item->quantity,
                    // фронт-цена = текущая цена позиции, чтобы checkPriceMatch не падал
                    'price' => $item->price,
                ];
            })
            ->all();

        $itemsValidation = $this->orderValidationService
            ->validateOrderItems($itemsForValidation, $promoCode);

        $totals = $this->orderValidationService
            ->calculateOrderTotals($itemsValidation['validated_items']);

        $totalPromoDiscount = $totals['total_promo_discount'] ?? 0;
        $totalItemsDiscount = $totals['total_discount'] ?? 0;
        $totalDiscountAmount = $totalItemsDiscount + $totalPromoDiscount;
        $totalOriginal = ($totals['order_total'] ?? 0) + $totalDiscountAmount;

        // Привязка к заказу
        $order->promo_code_id = $promoCode->id;
        $order->total_amount_original = round($totalOriginal, 2);
        $order->total_promo_discount = round($totalPromoDiscount, 2);
        $order->total_items_discount = round($totalItemsDiscount, 2);
        $order->discount_amount = round($totalDiscountAmount, 2);
        $order->save();

        // Создаём запись использования
        $promoCode->usages()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'discount_amount' => round($totalPromoDiscount, 2),
        ]);
        $promoCode->increment('times_used');

        $order->updateTotalAmount();
    }

    /**
     * Проверить можно ли редактировать заказ
     */
    public function canUpdate(Order $order): bool
    {
        // Можно редактировать заказы в любом статусе, кроме отмененных и доставленных
        return ! in_array($order->status, [
            OrderStatus::CANCELLED,
            OrderStatus::DELIVERED,
        ]);
    }
}
