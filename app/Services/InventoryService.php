<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    public function addStock($itemType, $itemId, $quantity, $pricePerUnit, $unitId, $receivedDate, $userId, $description = null)
    {
        return DB::transaction(function () use ($itemType, $itemId, $quantity, $pricePerUnit, $unitId, $receivedDate, $userId, $description) {
            // Создаем новую партию
            $batch = InventoryBatch::create([
                'item_type' => $itemType,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'price_per_unit' => $pricePerUnit,
                'unit_id' => $unitId,
                'received_date' => $receivedDate,
            ]);

            // Создаем транзакцию
            InventoryTransaction::create([
                'item_type' => $itemType,
                'item_id' => $itemId,
                'type' => 'incoming',
                'quantity' => $quantity,
                'price_per_unit' => $pricePerUnit,
                'unit_id' => $unitId,
                'batch_id' => $batch->id,
                'description' => $description,
                'user_id' => $userId,
            ]);

            // Обновляем баланс
            $this->updateBalance($itemType, $itemId, $quantity, $pricePerUnit, $unitId);

            return $batch;
        });
    }

    public function removeStock($itemType, $itemId, $quantity, $userId, $description = null)
    {
        return DB::transaction(function () use ($itemType, $itemId, $quantity, $userId, $description) {
            $balance = InventoryBalance::where('item_type', $itemType)
                ->where('item_id', $itemId)
                ->firstOrFail();

            if ($balance->total_quantity < $quantity) {
                throw new Exception("Недостаточно запасов");
            }

            $remainingQuantity = $quantity;
            $batches = InventoryBatch::where('item_type', $itemType)
                ->where('item_id', $itemId)
                ->where('quantity', '>', 0)
                ->orderBy('received_date', 'asc')
                ->get();

            foreach ($batches as $batch) {
                if ($remainingQuantity <= 0) break;

                $usedQuantity = min($batch->quantity, $remainingQuantity);
                $batch->quantity -= $usedQuantity;
                $batch->save();

                InventoryTransaction::create([
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'type' => 'outgoing',
                    'quantity' => $usedQuantity,
                    'price_per_unit' => $batch->price_per_unit,
                    'unit_id' => $batch->unit_id,
                    'batch_id' => $batch->id,
                    'description' => $description,
                    'user_id' => $userId,
                ]);

                $remainingQuantity -= $usedQuantity;
            }

            $this->updateBalance($itemType, $itemId, -$quantity, null, $balance->unit_id);

            return true;
        });
    }

    private function updateBalance($itemType, $itemId, $quantityChange, $newPrice = null, $unitId)
    {
        $balance = InventoryBalance::firstOrNew([
            'item_type' => $itemType,
            'item_id' => $itemId,
        ]);

        $newTotalQuantity = ($balance->total_quantity ?? 0) + $quantityChange;

        if ($newPrice !== null) {
            $oldValue = ($balance->total_quantity ?? 0) * ($balance->average_price ?? 0);
            $newValue = $quantityChange * $newPrice;
            $totalValue = $oldValue + $newValue;
            $newAveragePrice = $newTotalQuantity > 0 ? $totalValue / $newTotalQuantity : 0;
            $balance->average_price = $newAveragePrice;
        }

        $balance->total_quantity = $newTotalQuantity;
        $balance->unit_id = $unitId;
        $balance->save();
    }

    public function getStock($itemType, $itemId)
    {
        return InventoryBalance::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->with('unit')
            ->firstOrFail();
    }

    public function getTransactionHistory($itemType, $itemId, $fromDate = null, $toDate = null)
    {
        $query = InventoryTransaction::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->with(['unit', 'user', 'batch']);

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
