<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class UnitsTableSeeder extends Seeder
{
    public function run()
    {
        $units = [
            ['name' => 'Штука', 'abbreviation' => 'шт'],
            ['name' => 'Пара', 'abbreviation' => 'пар'],
            ['name' => 'Комплект', 'abbreviation' => 'компл'],
            ['name' => 'Набор', 'abbreviation' => 'наб'],
            ['name' => 'Упаковка', 'abbreviation' => 'уп'],

            ['name' => 'Килограмм', 'abbreviation' => 'кг'],
            ['name' => 'Грамм', 'abbreviation' => 'г'],
            ['name' => 'Миллиграмм', 'abbreviation' => 'мг'],

            ['name' => 'Литр', 'abbreviation' => 'л'],
            ['name' => 'Миллилитр', 'abbreviation' => 'мл'],
            ['name' => 'Кубический метр', 'abbreviation' => 'м³'],
            ['name' => 'Кубический сантиметр', 'abbreviation' => 'см³'],

            ['name' => 'Метр', 'abbreviation' => 'м'],

            ['name' => 'Квадратный метр', 'abbreviation' => 'м²'],
            ['name' => 'Квадратный сантиметр', 'abbreviation' => 'см²'],
            ['name' => 'Квадратный миллиметр', 'abbreviation' => 'мм²'],

            ['name' => 'Погонный метр', 'abbreviation' => 'пог. м'],

            ['name' => 'Мешок', 'abbreviation' => 'меш'],

            ['name' => 'Бобина', 'abbreviation' => 'боб'],
            ['name' => 'Моток', 'abbreviation' => 'мот'],
            ['name' => 'Пачка', 'abbreviation' => 'пач'],
            ['name' => 'Флакон', 'abbreviation' => 'флак'],
            ['name' => 'Ящик', 'abbreviation' => 'ящ'],
            ['name' => 'Контейнер', 'abbreviation' => 'конт'],
        ];
        foreach ($units as $unit) {
            DB::table('units')->insert([
                'name' => $unit['name'],
                'abbreviation' => $unit['abbreviation'],
            ]);
        }
    }
}
