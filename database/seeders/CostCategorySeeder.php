<?php

namespace Database\Seeders;

use App\Models\CostCategory;
use Illuminate\Database\Seeder;

class CostCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            // Затраты на оплату труда
            [
                'name' => 'Оплата труда операторов',
                'type' => CostCategory::TYPE_LABOR,
                'description' => 'Заработная плата производственного персонала'
            ],
            [
                'name' => 'Сдельная оплата',
                'type' => CostCategory::TYPE_LABOR,
                'description' => 'Оплата за единицу произведенной продукции'
            ],

            // Накладные расходы
            [
                'name' => 'Амортизация оборудования',
                'type' => CostCategory::TYPE_OVERHEAD,
                'description' => 'Расходы на амортизацию производственного оборудования'
            ],
            [
                'name' => 'Электроэнергия',
                'type' => CostCategory::TYPE_OVERHEAD,
                'description' => 'Затраты на электроэнергию в процессе производства'
            ],
            [
                'name' => 'Обслуживание оборудования',
                'type' => CostCategory::TYPE_OVERHEAD,
                'description' => 'Текущее обслуживание и ремонт оборудования'
            ],

            // Управленческие расходы
            [
                'name' => 'Контроль качества',
                'type' => CostCategory::TYPE_MANAGEMENT,
                'description' => 'Затраты на проверку качества продукции'
            ],
            [
                'name' => 'Управление производством',
                'type' => CostCategory::TYPE_MANAGEMENT,
                'description' => 'Затраты на управление производственным процессом'
            ]
        ];

        foreach ($categories as $category) {
            CostCategory::create($category);
        }
    }
}
