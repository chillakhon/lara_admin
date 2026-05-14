<?php

use App\Models\DeliveryZone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public $data = [
        [
            'name' => 'Москва — ПВЗ СДЭК',
            'country_code' => 'RU',
            'region_code' => 'RU-MOW',
            'city_code' => 'RU-MOW-MOW',
            'postal_code_pattern' => null,
            'delivery_method_id' => 1,
        ],
        [
            'name' => 'Санкт-Петербург — ПВЗ Boxberry',
            'country_code' => 'RU',
            'region_code' => 'RU-SPE',
            'city_code' => 'RU-SPE-SPE',
            'postal_code_pattern' => null,
            'delivery_method_id' => 1,
        ],
        [
            'name' => 'Центральный округ — СДЭК Курьер',
            'country_code' => 'RU',
            'region_code' => 'RU-CU',
            'city_code' => null,
            'postal_code_pattern' => null,
            'delivery_method_id' => 3,
        ],
        [
            'name' => 'Boxberry Курьер (южная Россия)',
            'country_code' => 'RU',
            'region_code' => null,
            'city_code' => null,
            'postal_code_pattern' => '6%',
            'delivery_method_id' => 4,
        ],
        [
            'name' => 'Почта России — до отделения',
            'country_code' => 'RU',
            'region_code' => null,
            'city_code' => null,
            'postal_code_pattern' => null,
            'delivery_method_id' => 5,
        ],
        [
            'name' => 'Почта России Курьер — Москва',
            'country_code' => 'RU',
            'region_code' => 'RU-MOW',
            'city_code' => null,
            'postal_code_pattern' => null,
            'delivery_method_id' => 6,
        ],
        [
            'name' => 'Почта России (общая зона)',
            'country_code' => 'RU',
            'region_code' => null,
            'city_code' => null,
            'postal_code_pattern' => null,
            'delivery_method_id' => 7,
        ],
        [
            'name' => 'Почта России до востребования (регион 630000)',
            'country_code' => 'RU',
            'region_code' => null,
            'city_code' => null,
            'postal_code_pattern' => '630000',
            'delivery_method_id' => 8,
        ],
        [
            'name' => 'ПВЗ международный — Казахстан',
            'country_code' => 'KZ',
            'region_code' => null,
            'city_code' => null,
            'postal_code_pattern' => null,
            'delivery_method_id' => 9,
        ],
        [
            'name' => 'Международный курьер — Беларусь',
            'country_code' => 'BY',
            'region_code' => null,
            'city_code' => null,
            'postal_code_pattern' => null,
            'delivery_method_id' => 10,
        ],
        [
            'name' => 'Почта России с извещением — Урал',
            'country_code' => 'RU',
            'region_code' => 'RU-UR',
            'city_code' => null,
            'postal_code_pattern' => null,
            'delivery_method_id' => 11,
        ],
        [
            'name' => 'Доставка курьером по всей России',
            'country_code' => 'RU',
            'region_code' => null,
            'city_code' => null,
            'postal_code_pattern' => null,
            'delivery_method_id' => 13,
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_zone_id')->nullable()->after('delivery_method_id');

            $table->foreign('delivery_zone_id', 'fk_delivery_zone_id')
                ->on('delivery_zones')
                ->references('id')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        DeliveryZone::insert($this->data);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('fk_delivery_zone_id');

            $table->dropColumn('delivery_zone_id');
        });

        DeliveryZone::whereNotNull("id")->delete();
    }
};
