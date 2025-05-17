<?php

use App\Models\Color;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    protected $colors = [
        ['name' => 'Белый', 'code' => '#FFFFFF'],
        ['name' => 'Чёрный', 'code' => '#000000'],
        ['name' => 'Красный', 'code' => '#FF0000'],
        ['name' => 'Синий', 'code' => '#0000FF'],
        ['name' => 'Зелёный', 'code' => '#00FF00'],
        ['name' => 'Жёлтый', 'code' => '#FFFF00'],
        ['name' => 'Оранжевый', 'code' => '#FFA500'],
        ['name' => 'Фиолетовый', 'code' => '#800080'],
        ['name' => 'Серый', 'code' => '#808080'],
        ['name' => 'Розовый', 'code' => '#FFC0CB'],
        ['name' => 'Коричневый', 'code' => '#A52A2A'],
        ['name' => 'Бежевый', 'code' => '#F5F5DC'],
        ['name' => 'Бордовый', 'code' => '#800000'],
        ['name' => 'Голубой', 'code' => '#87CEEB'],
        ['name' => 'Мятный', 'code' => '#98FF98'],
    ];
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        foreach ($this->colors as $color) {
            Color::create($color);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
