<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class InsertDeliveryMethodsData extends Migration
{
    public function up()
    {
        // Добавляем значение по умолчанию для поля provider_class, если его нет
        Schema::table('delivery_methods', function (Blueprint $table) {
            // Если еще нет, установим значение по умолчанию для provider_class
            if (!Schema::hasColumn('delivery_methods', 'provider_class')) {
                $table->string('provider_class')->default('DefaultProvider')->after('is_active');
            }
        });

        // Вставляем данные в таблицу
        DB::table('delivery_methods')->insert([
            ['name' => 'Пункт выдачи СДЭК', 'code' => 'cdek_pickup', 'description' => 'Самовывоз из пункта выдачи СДЭК', 'is_active' => 1, 'provider_class' => 'CdekProvider'],
            ['name' => 'Пункт самовывоза Boxberry', 'code' => 'boxberry_pickup', 'description' => 'Самовывоз из пункта выдачи Boxberry', 'is_active' => 1, 'provider_class' => 'BoxberryProvider'],
            ['name' => 'СДЭК: Курьерская доставка', 'code' => 'cdek_courier', 'description' => 'Доставка курьером СДЭК до двери', 'is_active' => 1, 'provider_class' => 'CdekProvider'],
            ['name' => 'Курьером Boxberry', 'code' => 'boxberry_courier', 'description' => 'Доставка курьером Boxberry до двери', 'is_active' => 1, 'provider_class' => 'BoxberryProvider'],
            ['name' => 'Доставка в отделение Почты России или почтомат', 'code' => 'russian_post_office', 'description' => 'Доставка в отделение Почты России или в почтомат', 'is_active' => 1, 'provider_class' => 'RussianPostProvider'],
            ['name' => 'Доставка курьером Почты России', 'code' => 'russian_post_courier', 'description' => 'Доставка курьером Почты России до двери', 'is_active' => 1, 'provider_class' => 'RussianPostProvider'],
            ['name' => 'Почта России', 'code' => 'russian_post', 'description' => 'Доставка Почтой России', 'is_active' => 1, 'provider_class' => 'RussianPostProvider'],
            ['name' => 'Почта России (до востребования)', 'code' => 'russian_post_on_demand', 'description' => 'Доставка Почтой России до востребования', 'is_active' => 1, 'provider_class' => 'RussianPostProvider'],
            ['name' => 'Доставка международных отправлений в ПВЗ', 'code' => 'international_pickup', 'description' => 'Доставка международных отправлений в пункт выдачи', 'is_active' => 1, 'provider_class' => 'InternationalProvider'],
            ['name' => 'Доставка международных отправлений курьером', 'code' => 'international_courier', 'description' => 'Доставка международных отправлений курьером до двери', 'is_active' => 1, 'provider_class' => 'InternationalProvider'],
            ['name' => 'Доставка в отделение Почты России с извещением', 'code' => 'russian_post_notification', 'description' => 'Доставка в отделение Почты России с извещением', 'is_active' => 1, 'provider_class' => 'RussianPostProvider'],
            ['name' => 'Почта России (не использовать)', 'code' => 'russian_post_deprecated', 'description' => 'Этот метод доставки больше не используется', 'is_active' => 0, 'provider_class' => 'RussianPostProvider'],
            ['name' => 'Курьером', 'code' => 'courier', 'description' => 'Доставка курьером', 'is_active' => 1, 'provider_class' => 'CourierProvider'],
        ]);
    }

    public function down()
    {
        // Удаление данных при откате миграции
        DB::table('delivery_methods')->whereIn('code', [
            'cdek_pickup', 'boxberry_pickup', 'cdek_courier', 'boxberry_courier',
            'russian_post_office', 'russian_post_courier', 'russian_post',
            'russian_post_on_demand', 'international_pickup', 'international_courier',
            'russian_post_notification', 'russian_post_deprecated', 'courier'
        ])->delete();
    }
}
