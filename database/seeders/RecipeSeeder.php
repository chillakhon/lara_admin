<?php

namespace Database\Seeders;

use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Добавляем техкарты для раздела SET
        $setRecipes = [
            [
                'name' => 'SET 1',
                'description' => 'Техкарта для SET 1',
                'output_quantity' => 1,
                'output_unit_id' => 1,
                'is_active' => 1,
                'instructions' => 'Инструкции для SET 1',
                'production_time' => 120,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SET 2',
                'description' => 'Техкарта для SET 2',
                'output_quantity' => 1,
                'output_unit_id' => 1,
                'is_active' => 1,
                'instructions' => 'Инструкции для SET 2',
                'production_time' => 120,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Вставляем техкарты для раздела SET
        DB::table('recipes')->insert($setRecipes);

        // Добавляем техкарты для раздела BOX
        $boxRecipes = [
            [
                'name' => 'BOX 1',
                'description' => 'Техкарта для BOX 1',
                'output_quantity' => 1,
                'output_unit_id' => 1,
                'is_active' => 1,
                'instructions' => 'Инструкции для BOX 1',
                'production_time' => 120,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BOX 2',
                'description' => 'Техкарта для BOX 2',
                'output_quantity' => 1,
                'output_unit_id' => 1,
                'is_active' => 1,
                'instructions' => 'Инструкции для BOX 2',
                'production_time' => 120,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Вставляем техкарты для раздела BOX
        DB::table('recipes')->insert($boxRecipes);

        // Добавляем компоненты для техкарт
        $recipeItems = [
            // Компоненты для SET 1
            [
                'recipe_id' => DB::table('recipes')->where('name', 'SET 1')->first()->id,
                'component_type' => ProductVariant::class,
                'component_id' => 1, // ID материала
                'quantity' => 1.0,
                'unit_id' => 1,
                'waste_percentage' => 0.0,
                'sort_order' => 1,
                'notes' => 'Вес (См., черный)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipe_id' => DB::table('recipes')->where('name', 'SET 1')->first()->id,
                'component_type' => ProductVariant::class,
                'component_id' => 2, // ID материала
                'quantity' => 1.0,
                'unit_id' => 1,
                'waste_percentage' => 0.0,
                'sort_order' => 2,
                'notes' => 'Выс (ОКСв., черный)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Компоненты для BOX 1
            [
                'recipe_id' => DB::table('recipes')->where('name', 'BOX 1')->first()->id,
                'component_type' =>  ProductVariant::class,
                'component_id' => 3, // ID материала
                'quantity' => 1.0,
                'unit_id' => 1,
                'waste_percentage' => 0.0,
                'sort_order' => 1,
                'notes' => 'Выс (См., черный)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipe_id' => DB::table('recipes')->where('name', 'BOX 1')->first()->id,
                'component_type' =>  ProductVariant::class,
                'component_id' => 4, // ID материала
                'quantity' => 1.0,
                'unit_id' => 1,
                'waste_percentage' => 0.0,
                'sort_order' => 2,
                'notes' => 'Выс (ОКс., черный)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Вставляем компоненты в таблицу recipe_items
        DB::table('recipe_items')->insert($recipeItems);
    }
}
