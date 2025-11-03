<?php

namespace App\Services\PromoCode;

use App\Models\Client;
use App\Models\PromoCode;
use Illuminate\Support\Facades\Log;

class PromoCodeValidationService
{
    /**
     * Валидация промокода для клиента
     *
     * @param string $code Код промокода
     * @param Client $client Клиент
     * @return array ['success' => bool, 'message' => string, 'promo_code' => PromoCode|null, 'code' => string|null]
     */
    public function validate(string $code, Client $client): array
    {
        // 1. Поиск промокода
        $promoCodeResult = $this->findPromoCode($code);
        if (!$promoCodeResult['success']) {
            return $promoCodeResult;
        }

        $promoCode = $promoCodeResult['promo_code'];

        // 2. Проверка активности и сроков действия
        $availabilityCheck = $this->checkAvailability($promoCode);
        if (!$availabilityCheck['success']) {
            return $availabilityCheck;
        }

        // 3. Проверка доступности для клиента
        $clientCheck = $this->checkClientEligibility($promoCode, $client);
        if (!$clientCheck['success']) {
            return $clientCheck;
        }

        // 4. Проверка лимитов использования
        $limitsCheck = $this->checkUsageLimits($promoCode, $client);
        if (!$limitsCheck['success']) {
            return $limitsCheck;
        }

        // Все проверки пройдены
        return [
            'success' => true,
            'promo_code' => $promoCode,
            'message' => 'Промокод успешно применён',
        ];
    }

    /**
     * Поиск промокода в базе данных
     *
     * @param string $code Код промокода
     * @return array
     */
    private function findPromoCode(string $code): array
    {
        $promoCode = PromoCode::where('code', $code)->first();

        if (!$promoCode) {
            Log::warning('Promo code not found', ['code' => $code]);

            return [
                'success' => false,
                'message' => 'Промокод не найден',
                'code' => 'PROMO_NOT_FOUND',
                'promo_code' => null,
            ];
        }

        return [
            'success' => true,
            'promo_code' => $promoCode,
        ];
    }

    /**
     * Проверка активности промокода и сроков действия
     *
     * @param PromoCode $promoCode Промокод
     * @return array
     */
    private function checkAvailability(PromoCode $promoCode): array
    {
        // Проверка активности
        if (!$promoCode->is_active) {
            Log::info('Promo code is inactive', ['code' => $promoCode->code]);

            return [
                'success' => false,
                'message' => 'Промокод неактивен',
                'code' => 'PROMO_INACTIVE',
            ];
        }

        $now = now();

        // Проверка срока начала действия
        if ($promoCode->starts_at && $promoCode->starts_at->isFuture()) {
            Log::info('Promo code not started yet', [
                'code' => $promoCode->code,
                'starts_at' => $promoCode->starts_at
            ]);

            return [
                'success' => false,
                'message' => 'Промокод ещё не активен. Начало действия: ' . $promoCode->starts_at->format('d.m.Y H:i'),
                'code' => 'PROMO_NOT_STARTED',
                'starts_at' => $promoCode->starts_at->format('Y-m-d H:i:s'),
            ];
        }

        // Проверка срока окончания действия
        if ($promoCode->expires_at && $promoCode->expires_at->isPast()) {
            Log::info('Promo code expired', [
                'code' => $promoCode->code,
                'expires_at' => $promoCode->expires_at
            ]);

            return [
                'success' => false,
                'message' => 'Срок действия промокода истёк ' . $promoCode->expires_at->format('d.m.Y H:i'),
                'code' => 'PROMO_EXPIRED',
                'expired_at' => $promoCode->expires_at->format('Y-m-d H:i:s'),
            ];
        }

        return ['success' => true];
    }

    /**
     * Проверка доступности промокода для конкретного клиента
     *
     * @param PromoCode $promoCode Промокод
     * @param Client $client Клиент
     * @return array
     */
    private function checkClientEligibility(PromoCode $promoCode, Client $client): array
    {
        // Если промокод доступен всем клиентам
        if ($promoCode->applies_to_all_clients) {
            return ['success' => true];
        }

        // Проверяем, есть ли привязка к конкретным клиентам
        $hasClientRestrictions = $promoCode->clients()->exists();

        if ($hasClientRestrictions) {
            $isEligible = $promoCode->clients()
                ->where('client_id', $client->id)
                ->exists();

            if (!$isEligible) {
                Log::warning('Promo code not available for client', [
                    'code' => $promoCode->code,
                    'client_id' => $client->id
                ]);

                return [
                    'success' => false,
                    'message' => 'Этот промокод недоступен для вашего аккаунта',
                    'code' => 'PROMO_NOT_FOR_CLIENT',
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Проверка лимитов использования промокода
     *
     * @param PromoCode $promoCode Промокод
     * @param Client $client Клиент
     * @return array
     */
    private function checkUsageLimits(PromoCode $promoCode, Client $client): array
    {
        // Проверка общего лимита использований
        if ($promoCode->max_uses && $promoCode->times_uses >= $promoCode->max_uses) {
            Log::info('Promo code usage limit reached', [
                'code' => $promoCode->code,
                'max_uses' => $promoCode->max_uses,
                'times_uses' => $promoCode->times_uses
            ]);

            return [
                'success' => false,
                'message' => 'Лимит использований промокода исчерпан',
                'code' => 'PROMO_USAGE_LIMIT_REACHED',
                'max_uses' => $promoCode->max_uses,
                'times_uses' => $promoCode->times_uses,
            ];
        }

        // Проверка использования конкретным клиентом
        $clientUsage = $promoCode->usages()
            ->where('client_id', $client->id)
            ->exists();

        if ($clientUsage) {
            Log::info('Client already used promo code', [
                'code' => $promoCode->code,
                'client_id' => $client->id
            ]);

            return [
                'success' => false,
                'message' => 'Вы уже использовали этот промокод',
                'code' => 'PROMO_ALREADY_USED',
            ];
        }

        return ['success' => true];
    }

    /**
     * Получить детальную информацию о промокоде
     *
     * @param PromoCode $promoCode Промокод
     * @return array Детальная информация
     */
    public function getPromoCodeDetails(PromoCode $promoCode): array
    {
        $remainingUses = null;
        if ($promoCode->max_uses) {
            $remainingUses = max(0, $promoCode->max_uses - $promoCode->times_uses);
        }

        return [
            'id' => $promoCode->id,
            'code' => $promoCode->code,
            'description' => $promoCode->description,
            'image' => $promoCode->image_url,

            // Информация о скидке
            'discount_type' => $promoCode->discount_type,
            'discount_amount' => $promoCode->discount_amount,
            'discount_behavior' => $promoCode->discount_behavior,

            // Применимость
            'type' => $promoCode->type,
            'applies_to_all_products' => $promoCode->applies_to_all_products,
            'applies_to_all_clients' => $promoCode->applies_to_all_clients,

            // Даты
            'starts_at' => $promoCode->starts_at?->format('Y-m-d H:i:s'),
            'expires_at' => $promoCode->expires_at?->format('Y-m-d H:i:s'),
            'is_active' => $promoCode->is_active,

            // Статистика использования
            'max_uses' => $promoCode->max_uses,
            'times_uses' => $promoCode->times_uses,
            'remaining_uses' => $remainingUses,
            'usage_percentage' => $promoCode->max_uses
                ? round(($promoCode->times_uses / $promoCode->max_uses) * 100, 2)
                : 0,
        ];
    }

    /**
     * Проверить, доступен ли промокод для конкретных товаров
     *
     * @param PromoCode $promoCode Промокод
     * @param array $productIds Массив ID товаров
     * @return array ['applicable' => array, 'not_applicable' => array]
     */
    public function checkProductApplicability(PromoCode $promoCode, array $productIds): array
    {
        $applicable = [];
        $notApplicable = [];

        // Если промокод применяется ко всем товарам
        if ($promoCode->type === 'all' || $promoCode->applies_to_all_products) {
            return [
                'applicable' => $productIds,
                'not_applicable' => [],
            ];
        }

        // Если промокод для конкретных товаров
        if ($promoCode->type === 'specific') {
            $allowedProductIds = $promoCode->products()->pluck('product_id')->toArray();

            foreach ($productIds as $productId) {
                if (in_array($productId, $allowedProductIds)) {
                    $applicable[] = $productId;
                } else {
                    $notApplicable[] = $productId;
                }
            }
        }

        return [
            'applicable' => $applicable,
            'not_applicable' => $notApplicable,
        ];
    }

    /**
     * Проверить минимальную сумму заказа (если будет добавлено в модель)
     *
     * @param PromoCode $promoCode Промокод
     * @param float $orderTotal Сумма заказа
     * @return array
     */
    public function checkMinimumOrderAmount(PromoCode $promoCode, float $orderTotal): array
    {
        // Если в будущем добавится поле min_order_amount
        if (isset($promoCode->min_order_amount) && $promoCode->min_order_amount > 0) {
            if ($orderTotal < $promoCode->min_order_amount) {
                return [
                    'success' => false,
                    'message' => "Минимальная сумма заказа для применения промокода: {$promoCode->min_order_amount} ₽",
                    'code' => 'MIN_ORDER_AMOUNT_NOT_MET',
                    'required_amount' => $promoCode->min_order_amount,
                    'current_amount' => $orderTotal,
                    'difference' => $promoCode->min_order_amount - $orderTotal,
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Логирование использования промокода
     *
     * @param PromoCode $promoCode Промокод
     * @param Client $client Клиент
     * @param array $context Дополнительный контекст
     * @return void
     */
    public function logPromoCodeUsage(PromoCode $promoCode, Client $client, array $context = []): void
    {
        Log::info('Promo code applied successfully', [
            'promo_code_id' => $promoCode->id,
            'code' => $promoCode->code,
            'client_id' => $client->id,
            'client_email' => $client->email ?? null,
            'discount_type' => $promoCode->discount_type,
            'discount_amount' => $promoCode->discount_amount,
            'discount_behavior' => $promoCode->discount_behavior,
            ...$context
        ]);
    }
}
