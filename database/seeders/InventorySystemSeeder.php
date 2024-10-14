<?php

namespace Database\Seeders;

use App\Models\InventoryBalance;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем единицы измерения, если их еще нет
        if (Unit::count() == 0) {
            Unit::factory()->count(5)->create();
        }

        // Создаем материалы
        $materials = Material::factory()->count(20)->create();

        foreach ($materials as $material) {
            // Создаем несколько партий для материала
            $batches = InventoryBatch::factory()->count(3)->create([
                'item_type' => Material::class,
                'item_id' => $material->id,
                'unit_id' => $material->unit_id,
            ]);

            // Создаем баланс для материала
            $balance = InventoryBalance::factory()->create([
                'item_type' => Material::class,
                'item_id' => $material->id,
                'unit_id' => $material->unit_id,
                'total_quantity' => $batches->sum('quantity'),
                'average_price' => $batches->avg('price_per_unit'),
            ]);

            // Создаем транзакции для материала
            foreach ($batches as $batch) {
                InventoryTransaction::factory()->create([
                    'item_type' => Material::class,
                    'item_id' => $material->id,
                    'unit_id' => $material->unit_id,
                    'batch_id' => $batch->id,
                    'type' => 'incoming',
                    'quantity' => $batch->quantity,
                    'price_per_unit' => $batch->price_per_unit,
                ]);
            }

            // Создаем несколько исходящих транзакций
            InventoryTransaction::factory()->count(2)->create([
                'item_type' => Material::class,
                'item_id' => $material->id,
                'unit_id' => $material->unit_id,
                'type' => 'outgoing',
                'quantity' => $balance->total_quantity / 3,
                'price_per_unit' => $balance->average_price,
            ]);
        }
    }
}
