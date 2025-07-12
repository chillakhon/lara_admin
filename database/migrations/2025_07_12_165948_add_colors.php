<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('colors', function (Blueprint $table) {
            $table->string('normalized_name')->nullable()->after('name');
        });

        $existingColors = DB::table('colors')->get();
        foreach ($existingColors as $color) {
            $normalized = str_replace('ё', 'е', mb_strtolower($color->name));
            DB::table('colors')->where('id', $color->id)->update([
                'normalized_name' => $normalized,
            ]);
        }

        $newColors = [
            ['name' => 'Лаймовый', 'code' => '#BFFF00'],
            ['name' => 'Индиго', 'code' => '#4B0082'],
            ['name' => 'Бирюзовый', 'code' => '#40E0D0'],
            ['name' => 'Салатовый', 'code' => '#7CFC00'],
            ['name' => 'Небесный', 'code' => '#87CEFA'],
            ['name' => 'Сливовый', 'code' => '#8E4585'],
            ['name' => 'Оливковый', 'code' => '#808000'],
            ['name' => 'Малиновый', 'code' => '#DC143C'],
            ['name' => 'Песочный', 'code' => '#F4A460'],
            ['name' => 'Персиковый', 'code' => '#FFE5B4'],
            ['name' => 'Янтарный', 'code' => '#FFBF00'],
            ['name' => 'Светло-серый', 'code' => '#D3D3D3'],
            ['name' => 'Темно-синий', 'code' => '#00008B'],
        ];

        foreach ($newColors as $color) {
            $normalized = str_replace('ё', 'е', mb_strtolower($color['name']));

            $exists = DB::table('colors')
                ->where('normalized_name', $normalized)
                ->exists();

            if (!$exists) {
                DB::table('colors')->insert([
                    'name' => $color['name'],
                    'normalized_name' => $normalized,
                    'code' => $color['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удалим добавленные цвета
        $newColorNames = [
            'Лаймовый',
            'Индиго',
            'Бирюзовый',
            'Салатовый',
            'Небесный',
            'Сливовый',
            'Оливковый',
            'Малиновый',
            'Песочный',
            'Персиковый',
            'Янтарный',
            'Светло-серый',
            'Темно-синий',
        ];

        DB::table('colors')->whereIn('name', $newColorNames)->delete();

        // Удалим колонку normalized_name
        Schema::table('colors', function (Blueprint $table) {
            $table->dropColumn('normalized_name');
        });
    }
};
