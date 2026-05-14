<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Нормализация orders.payment_method к новому набору кодов.
 *
 * Канонические коды (соответствуют списку в админке/витрине):
 *   - card_ru                — Оплата картой РФ
 *   - sberpay                — SberPay, рассрочка, иностранная карта
 *   - yandex_pay_split       — Яндекс Пэй и Сплит
 *   - cash_on_delivery       — Наличными или картой при получении
 *   - pickup_payment         — Оплата в точке самовывоза
 *   - podeli                 — Подели
 *   - robokassa_mokka        — Robokassa X Мокка
 *   - robokassa_yandex_split — Robokassa X Яндекс Сплит
 *
 * Старые значения мапятся к ближайшему канону.
 */
return new class extends Migration
{
    /**
     * old code => new code
     */
    private const MAPPING = [
        'card' => 'card_ru',
        'yookassa' => 'card_ru',
        'online' => 'card_ru',
        'bank_transfer' => 'card_ru',
        'sbp' => 'sberpay',
        'sberpay' => 'sberpay',
        'split' => 'yandex_pay_split',
        'yandex_pay' => 'yandex_pay_split',
        'yandex_pay_split' => 'yandex_pay_split',
        'cash' => 'cash_on_delivery',
        'cod' => 'cash_on_delivery',
        'cash_on_delivery' => 'cash_on_delivery',
        'pickup' => 'pickup_payment',
        'pickup_payment' => 'pickup_payment',
        'podeli' => 'podeli',
        'robokassa_mokka' => 'robokassa_mokka',
        'robokassa_yandex_split' => 'robokassa_yandex_split',
        'card_ru' => 'card_ru',
    ];

    public function up(): void
    {
        foreach (self::MAPPING as $old => $new) {
            if ($old === $new) {
                continue;
            }
            DB::table('orders')
                ->where('payment_method', $old)
                ->update(['payment_method' => $new]);
        }

        // Любые прочие нераспознанные значения (нерпустые и не входящие в канон)
        // схлопываем к 'card_ru' как безопасному дефолту.
        $canonical = array_values(array_unique(array_values(self::MAPPING)));

        DB::table('orders')
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', '')
            ->whereNotIn('payment_method', $canonical)
            ->update(['payment_method' => 'card_ru']);
    }

    public function down(): void
    {
        // Откат не выполняется: исходные значения утеряны после нормализации.
    }
};
