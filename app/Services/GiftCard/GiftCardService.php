<?php

namespace App\Services\GiftCard;


use App\Models\GiftCard\GiftCard;
use App\Models\GiftCard\GiftCardTransaction;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GiftCardService
{
    public function __construct(
        protected GiftCardCodeGenerator $codeGenerator
    ) {}

    /**
     * Создание подарочной карты после покупки
     */
    public function createFromOrder(Order $order, array $giftCardData, float $nominal): GiftCard
    {
        DB::beginTransaction();

        try {
            // Генерируем уникальный код
            $code = $this->codeGenerator->generate();

            // Определяем получателя
            $recipientData = $this->resolveRecipientData($order, $giftCardData);

            // Определяем время отправки
            $scheduledAt = $this->resolveScheduledAt($giftCardData);

            // Создаём карту
            $giftCard = GiftCard::create([
                'code' => $code,
                'purchase_order_id' => $order->id,
                'nominal' => $nominal,
                'balance' => $nominal,
                'type' => $giftCardData['type'] ?? GiftCard::TYPE_ELECTRONIC,
                'status' => GiftCard::STATUS_INACTIVE,

                // Отправитель
                'sender_name' => $giftCardData['sender_name'] ?? null,
                'sender_email' => $giftCardData['sender_email'] ?? $order->client->email ?? null,
                'sender_phone' => $giftCardData['sender_phone'] ?? $order->client->profile->phone ?? null,

                // Получатель
                'recipient_name' => $recipientData['name'],
                'recipient_email' => $recipientData['email'],
                'recipient_phone' => $recipientData['phone'],

                // Доставка
                'message' => $giftCardData['message'] ?? null,
                'delivery_channel' => $giftCardData['delivery_channel'] ?? GiftCard::CHANNEL_EMAIL,
                'scheduled_at' => $scheduledAt,
                'timezone' => $giftCardData['timezone'] ?? null,
            ]);

            // Создаём транзакцию покупки
            GiftCardTransaction::createPurchase($giftCard, $order);

            DB::commit();

//            Log::info('Gift card created', [
//                'gift_card_id' => $giftCard->id,
//                'code' => $giftCard->code,
//                'order_id' => $order->id,
//            ]);

            return $giftCard;

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to create gift card', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Применение подарочной карты к заказу
     */
    public function applyToOrder(GiftCard $giftCard, Order $order, float $orderTotal): array
    {
        DB::beginTransaction();

        try {
            // Проверяем, можно ли использовать карту
            if (!$giftCard->isActive()) {
                throw new Exception('Подарочная карта неактивна');
            }

            if ($giftCard->balance <= 0) {
                throw new Exception('На подарочной карте недостаточно средств');
            }

            // Рассчитываем сумму к списанию
            $amountToUse = min($giftCard->balance, $orderTotal);

            // Списываем с карты
            $giftCard->deduct($amountToUse);

            // Создаём транзакцию использования
            GiftCardTransaction::createUsage($giftCard, $order, $amountToUse);

            DB::commit();

//            Log::info('Gift card applied to order', [
//                'gift_card_id' => $giftCard->id,
//                'order_id' => $order->id,
//                'amount_used' => $amountToUse,
//                'remaining_balance' => $giftCard->balance,
//            ]);

            return [
                'success' => true,
                'amount_used' => $amountToUse,
                'remaining_balance' => $giftCard->balance,
                'order_total_after' => max(0, $orderTotal - $amountToUse),
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to apply gift card', [
                'gift_card_id' => $giftCard->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Валидация подарочной карты
     */
    public function validate(string $code): array
    {
        $cleanCode = $this->codeGenerator->cleanCode($code);
        $giftCard = GiftCard::where('code', $cleanCode)->first();

        if (!$giftCard) {
            return [
                'valid' => false,
                'message' => 'Подарочная карта с таким кодом не найдена',
            ];
        }

        if ($giftCard->status === GiftCard::STATUS_CANCELLED) {
            return [
                'valid' => false,
                'message' => 'Эта подарочная карта была аннулирована',
            ];
        }
        if ($giftCard->status === GiftCard::STATUS_INACTIVE) {
            return [
                'valid' => false,
                'message' => 'Эта подарочная карта не активно',
            ];
        }

        if ($giftCard->balance <= 0) {
            return [
                'valid' => false,
                'message' => 'На этой подарочной карте недостаточно средств',
            ];
        }

        return [
            'valid' => true,
            'gift_card' => $giftCard,
            'balance' => $giftCard->balance,
            'message' => "Доступно к оплате: {$giftCard->balance} ₽",
        ];
    }

    /**
     * Возврат средств на карту
     */
    public function refund(GiftCard $giftCard, Order $order, float $amount): void
    {
        DB::beginTransaction();

        try {
            // Восстанавливаем баланс
            $giftCard->refund($amount);

            // Создаём транзакцию возврата
            GiftCardTransaction::createRefund($giftCard, $order, $amount);

            DB::commit();

            Log::info('Gift card refunded', [
                'gift_card_id' => $giftCard->id,
                'order_id' => $order->id,
                'amount' => $amount,
                'new_balance' => $giftCard->balance,
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to refund gift card', [
                'gift_card_id' => $giftCard->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Аннулирование карты
     */
    public function cancel(GiftCard $giftCard, ?string $reason = null): void
    {
        DB::beginTransaction();

        try {
            $giftCard->cancel();

            GiftCardTransaction::createCancellation($giftCard, $reason);

            DB::commit();

//            Log::info('Gift card cancelled', [
//                'gift_card_id' => $giftCard->id,
//                'reason' => $reason,
//            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to cancel gift card', [
                'gift_card_id' => $giftCard->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Определение данных получателя
     */
    private function resolveRecipientData(Order $order, array $giftCardData): array
    {
        $recipientType = $giftCardData['recipient_type'] ?? 'self';

        if ($recipientType === 'self') {
            return [
                'name' => $order->client->profile->first_name ?? $giftCardData['sender_name'],
                'email' => $order->client->email,
                'phone' => $order->client->profile->phone ?? null,
            ];
        }

        return [
            'name' => $giftCardData['recipient_name'],
            'email' => $giftCardData['recipient_email'],
            'phone' => $giftCardData['recipient_phone'] ?? null,
        ];
    }

    /**
     * Определение времени отправки
     */
    private function resolveScheduledAt(array $giftCardData): ?string
    {
        $deliveryType = $giftCardData['delivery_type'] ?? 'immediate';

        if ($deliveryType === 'immediate') {
            return now();
        }

        try {
            $timezone = $giftCardData['timezone'] ?? config('app.timezone');

            // Вариант 1: Есть готовая дата в scheduled_at (приоритет)
            if (!empty($giftCardData['scheduled_at'])) {
                $date = \Carbon\Carbon::parse($giftCardData['scheduled_at']);
                return $date->format('Y-m-d H:i:s');
            }

            // Вариант 2: Есть scheduled_date
            if (!empty($giftCardData['scheduled_date'])) {
                $dateStr = $giftCardData['scheduled_date'];

                // Проверяем, это ISO-формат (содержит 'T') или простая дата
                if (str_contains($dateStr, 'T')) {
                    // Это ISO-дата: "2026-01-06T10:49:00.000Z"
                    // Извлекаем ТОЛЬКО дату (игнорируем время из ISO)
                    $date = \Carbon\Carbon::parse($dateStr);
                    $dateOnly = $date->format('Y-m-d'); // "2026-01-06"

                    // Берём время из scheduled_time
                    $timeStr = $giftCardData['scheduled_time'] ?? '12:00'; // "13:55"

                    // Создаём дату с нужным временем в указанной таймзоне
                    $finalDate = \Carbon\Carbon::createFromFormat(
                        'Y-m-d H:i',
                        "{$dateOnly} {$timeStr}",
                        $timezone // Europe/Moscow
                    );

                    return $finalDate->format('Y-m-d H:i:s');
                } else {
                    // Это простая дата: "2026-01-06"
                    $timeStr = $giftCardData['scheduled_time'] ?? '12:00';

                    $date = \Carbon\Carbon::createFromFormat(
                        'Y-m-d H:i',
                        "{$dateStr} {$timeStr}",
                        $timezone
                    );

                    return $date->format('Y-m-d H:i:s');
                }
            }

            return now();

        } catch (\Exception $e) {
            Log::warning('Failed to parse scheduled date', [
                'data' => $giftCardData,
                'error' => $e->getMessage(),
            ]);

            return now();
        }
    }
}
