<?php

namespace Database\Seeders;

use App\Models\DeliveryMethod;
use Illuminate\Database\Seeder;

/**
 * Сидер способов доставки.
 *
 * Воспроизводит список вариантов, который InSales показывает в карточке
 * заказа (admin/order/:id) в выпадающем меню выбора доставки.
 *
 * Идемпотентен — пересоздаёт записи по `code` через updateOrCreate.
 */
class DeliveryMethodSeeder extends Seeder
{
    public function run(): void
    {
        $cdek = \App\Services\Delivery\CdekDeliveryService::class;
        $generic = \App\Services\Delivery\DeliveryService::class;

        $methods = [
            // СДЭК
            [
                'code' => 'cdek_pickup',
                'name' => 'Пункт выдачи СДЭК',
                'description' => 'Самовывоз из пункта выдачи СДЭК',
                'provider_class' => $cdek,
                'settings' => ['kind' => 'pickup', 'company' => 'cdek'],
            ],
            [
                'code' => 'cdek_courier',
                'name' => 'СДЭК: Курьерская доставка',
                'description' => 'Курьерская доставка СДЭК до двери',
                'provider_class' => $cdek,
                'settings' => ['kind' => 'courier', 'company' => 'cdek'],
            ],

            // Яндекс.Доставка
            [
                'code' => 'yandex_pickup',
                'name' => 'Пункт самовывоза Яндекс.Доставки',
                'description' => 'Самовывоз из ПВЗ Яндекс.Доставки',
                'provider_class' => $generic,
                'settings' => ['kind' => 'pickup', 'company' => 'yandex'],
            ],
            [
                'code' => 'yandex_courier',
                'name' => 'Курьером Яндекс.Доставки',
                'description' => 'Курьерская доставка Яндекс.Доставки',
                'provider_class' => $generic,
                'settings' => ['kind' => 'courier', 'company' => 'yandex'],
            ],

            // Почта России
            [
                'code' => 'russian_post_office',
                'name' => 'Доставка в отделение Почты России или почтомат',
                'description' => 'Получение в отделении Почты России или в почтомате',
                'provider_class' => $generic,
                'settings' => ['kind' => 'pickup', 'company' => 'pochta'],
            ],
            [
                'code' => 'russian_post_courier',
                'name' => 'Доставка курьером Почты России',
                'description' => 'Курьерская доставка Почтой России',
                'provider_class' => $generic,
                'settings' => ['kind' => 'courier', 'company' => 'pochta'],
            ],
            [
                'code' => 'russian_post_on_demand',
                'name' => 'Почта России (до востребования)',
                'description' => 'Получение по запросу в отделении Почты России',
                'provider_class' => $generic,
                'settings' => ['kind' => 'on_demand', 'company' => 'pochta'],
            ],
            [
                'code' => 'russian_post_notification',
                'name' => 'Доставка в отделение Почты России с извещением',
                'description' => 'Доставка с уведомлением о вручении',
                'provider_class' => $generic,
                'settings' => ['kind' => 'pickup_with_notice', 'company' => 'pochta'],
            ],
            [
                'code' => 'international_pickup',
                'name' => 'Доставка международных отправлений в ПВЗ',
                'description' => 'Международная доставка в пункт выдачи',
                'provider_class' => $generic,
                'settings' => ['kind' => 'pickup', 'company' => 'pochta', 'international' => true],
            ],
            [
                'code' => 'international_courier',
                'name' => 'Доставка международных отправлений курьером',
                'description' => 'Международная курьерская доставка',
                'provider_class' => $generic,
                'settings' => ['kind' => 'courier', 'company' => 'pochta', 'international' => true],
            ],

            // Базовые
            [
                'code' => 'courier',
                'name' => 'Курьером',
                'description' => 'Доставка собственным курьером',
                'provider_class' => $generic,
                'settings' => ['kind' => 'courier', 'company' => 'self'],
            ],
            [
                'code' => 'email',
                'name' => 'Электронная почта',
                'description' => 'Доставка на e-mail (электронные товары, сертификаты)',
                'provider_class' => $generic,
                'settings' => ['kind' => 'email', 'company' => 'self'],
            ],
            [
                'code' => 'none',
                'name' => 'Доставка не требуется',
                'description' => 'Доставка не требуется',
                'provider_class' => $generic,
                'settings' => ['kind' => 'none', 'company' => 'self'],
            ],
        ];

        foreach ($methods as $method) {
            DeliveryMethod::withTrashed()->updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'description' => $method['description'],
                    'provider_class' => $method['provider_class'],
                    'settings' => $method['settings'],
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
