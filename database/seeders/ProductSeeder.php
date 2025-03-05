<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создание товара "LOVE SET"
        Product::create([
            'name' => 'LOVE SET',
            'slug' => 'love-set',
            'description' => 'Состав сета:
— 1 трусы LOVE AGAIN, цвет красный
— 1 трусы LOVE AGAIN, цвет розовая пудра
— 2 трусов LOVE AGAIN, цвет черный
Цвет: черный
Состав: 92% хлопок, 8% лайкра

Цвет: красный, розовая пудра
Состав: 95% бамбук, 5% лайкра

Впитываемость LOVE: Normal 15 ml = 2-3 тампона (три капли)

Модель Love Again идеально подходит, если у вас умеренные выделения и вам нужно не более 3 тампонов или 3 прокладок в день.

Классическая комфортная посадка, такие трусы выдерживают до 12 часов менструации благодаря специальной 4-слойной конструкции и ластовице. Они полностью заменят вам тампоны, прокладки, чаши или любые другие средства гигиены.

Вы забудете, что такое раздражение, зуд и неприятный запах. Нежная приятная ткань без шелеста. Единственный вопрос, который задают себе наши клиентки: "Почему я раньше о них не знала?".

Простой уход и комфортное использование!',
            'type' => 'simple',
            'default_unit_id' => null,
            'is_active' => true,
            'has_variants' => false,
            'allow_preorder' => false,
            'after_purchase_processing_time' => 0,
        ]);

        // Создание товара "Любимый SET от доктора Садовская"
        Product::create([
            'name' => 'Любимый SET от доктора Садовская',
            'slug' => 'lyubimyy-set-ot-doktora-sadovskaya',
            'description' => 'Состав сета:
— 1 трусы BODY AGAIN
— 1 трусы SEXY AGAIN

Цвет: черный
Состав: 92% хлопок, 8% лайкра
Впитываемость BODY: Normal 15 ml = 2-3 тампона (три капли)
Впитываемость SEXY: Normal 15 ml = 2-3 тампона (три капли)',
            'type' => 'simple',
            'default_unit_id' => null,
            'is_active' => true,
            'has_variants' => false,
            'allow_preorder' => false,
            'after_purchase_processing_time' => 0,
        ]);
    }
}
