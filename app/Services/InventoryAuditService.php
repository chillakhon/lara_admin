<?php

namespace App\Services;

use App\Models\InventoryAudit;
use App\Models\Product;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class InventoryAuditService
{
    public function createAudit(): InventoryAudit
    {
        try {
            DB::beginTransaction();
            
            $audit = InventoryAudit::create([
                'number' => 'INV-' . date('YmdHis'),
                'created_by' => auth()->id(),
                'status' => InventoryAudit::STATUS_DRAFT
            ]);

            if (!$audit || !$audit->id) {
                throw new \Exception('Failed to create audit record');
            }

            \Log::info('Created audit:', [
                'id' => $audit->id,
                'number' => $audit->number
            ]);

            // Добавляем все материалы
            $materials = Material::with(['unit', 'inventoryBalance'])->get();
            foreach ($materials as $material) {
                $audit->items()->create([
                    'item_type' => Material::class,
                    'item_id' => $material->id,
                    'unit_id' => $material->unit_id,
                    'expected_quantity' => $material->inventoryBalance?->total_quantity ?? 0,
                    'cost_per_unit' => $material->cost ?? 0,
                ]);
            }

            // Добавляем все товары
            $products = Product::with(['unit', 'inventoryBalance'])->get();
            foreach ($products as $product) {
                $audit->items()->create([
                    'item_type' => Product::class,
                    'item_id' => $product->id,
                    'unit_id' => $product->unit_id ?? $product->default_unit_id,
                    'expected_quantity' => $product->inventoryBalance?->total_quantity ?? 0,
                    'cost_per_unit' => $product->price ?? 0,
                ]);
            }

            DB::commit();

            // Загружаем свежую версию с отношениями
            $loadedAudit = InventoryAudit::with(['items.item', 'items.unit', 'creator'])
                ->findOrFail($audit->id);

            return $loadedAudit;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating audit: ' . $e->getMessage());
            throw $e;
        }
    }

    public function startAudit(InventoryAudit $audit): void
    {
        if ($audit->status !== InventoryAudit::STATUS_DRAFT) {
            throw new \Exception('Инвентаризация не может быть начата');
        }

        $audit->update([
            'status' => InventoryAudit::STATUS_IN_PROGRESS,
            'started_at' => now()
        ]);
    }

    public function completeAudit(InventoryAudit $audit, bool $updateStock = true): void
    {
        if ($audit->status !== InventoryAudit::STATUS_IN_PROGRESS) {
            throw new \Exception('Инвентаризация не может быть завершена');
        }

        if ($updateStock) {
            foreach ($audit->items as $item) {
                if ($item->actual_quantity !== null) {
                    // Обновляем остатки в InventoryBalance
                    $balance = $item->item->inventoryBalance;
                    if ($balance) {
                        $balance->update([
                            'total_quantity' => $item->actual_quantity
                        ]);
                    }
                }
            }
        }

        $audit->update([
            'status' => InventoryAudit::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => auth()->id()
        ]);
    }

    public function cancelAudit(InventoryAudit $audit): void
    {
        if (!in_array($audit->status, [InventoryAudit::STATUS_DRAFT, InventoryAudit::STATUS_IN_PROGRESS])) {
            throw new \Exception('Инвентаризация не может быть отменена');
        }

        $audit->update([
            'status' => InventoryAudit::STATUS_CANCELLED
        ]);
    }
} 