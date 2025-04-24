<?php

namespace App\Services;

use App\Models\ComponentReservation;
use App\Models\InventoryBatch;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\ProductionBatch;
use Illuminate\Database\Eloquent\Collection;
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

    public function reserveComponents(ProductionBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            foreach ($batch->recipe->material_items as $item) {
                $requiredQuantity = $this->calculateRequiredQuantity($item, $batch->planned_quantity);

                $reservation = ComponentReservation::create([
                    'production_batch_id' => $batch->id,
                    'component_type' => $item->component_type,
                    'component_id' => $item->component_id,
                    'quantity' => $requiredQuantity,
                    'unit_id' => $item->unit_id,
                    'expires_at' => now()->addDay() // Или другой период
                ]);

                // Обновляем доступное количество
                $this->updateAvailableQuantity($item->component_type, $item->component_id);
            }
        });
    }

    public function releaseReservation(ComponentReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $reservation->delete();

            // Обновляем доступное количество
            $this->updateAvailableQuantity(
                $reservation->component_type,
                $reservation->component_id
            );
        });
    }

    public function trackComponentUsage(array $filters = null): array
    {
        $query = InventoryTransaction::query();

        if ($filters) {
            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }
        }

        $transactions = $query->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_incoming' => $transactions->where('type', 'incoming')->sum('quantity'),
            'total_outgoing' => $transactions->where('type', 'outgoing')->sum('quantity'),
            'average_price' => $transactions->average('price_per_unit'),
            'components_usage' => $transactions
                ->where('type', 'outgoing')
                ->groupBy('item_type', 'item_id')
                ->map(function ($group) {
                    return [
                        'quantity' => $group->sum('quantity'),
                        'total_cost' => $group->sum(function ($transaction) {
                            return $transaction->quantity * $transaction->price_per_unit;
                        })
                    ];
                })
        ];
    }

    protected function updateAvailableQuantity(string $itemType, int $itemId): void
    {
        $totalQuantity = InventoryBatch::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->sum('quantity');

        $reservedQuantity = ComponentReservation::where('component_type', $itemType)
            ->where('component_id', $itemId)
            ->where('expires_at', '>', now())
            ->sum('quantity');

        $availableQuantity = $totalQuantity - $reservedQuantity;

        // Обновляем баланс
        $balance = InventoryBalance::firstOrCreate([
            'item_type' => $itemType,
            'item_id' => $itemId
        ]);

        $balance->update([
            'total_quantity' => $totalQuantity,
            'available_quantity' => $availableQuantity
        ]);
    }

    public function getAvailableQuantity(string $itemType, int $itemId): float
    {
        $balance = InventoryBalance::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->first();

        if (!$balance) {
            return 0;
        }

        // Получаем общее количество на складе
        $totalQuantity = $balance->total_quantity ?? 0;

        // Получаем зарезервированное количество
        $reservedQuantity = ComponentReservation::where('component_type', $itemType)
            ->where('component_id', $itemId)
            ->where('expires_at', '>', now())
            ->sum('quantity');

        // Возвращаем доступное количество (общее минус зарезервированное)
        return $totalQuantity - $reservedQuantity;
    }

    // Может быть полезно также добавить метод для получения детальной информации
    public function getItemAvailability(string $itemType, int $itemId): array
    {
        $balance = InventoryBalance::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->first();

        $totalQuantity = $balance->total_quantity ?? 0;
        $reservedQuantity = ComponentReservation::where('component_type', $itemType)
            ->where('component_id', $itemId)
            ->where('expires_at', '>', now())
            ->sum('quantity');

        $availableQuantity = $totalQuantity - $reservedQuantity;

        return [
            'total_quantity' => $totalQuantity,
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => $availableQuantity,
            'unit' => $balance->unit ?? null,
            'last_updated' => $balance ? $balance->updated_at : null,
        ];
    }

    // Также добавим метод для проверки доступности конкретного количества
    public function checkAvailability(string $itemType, int $itemId, float $requiredQuantity): bool
    {
        return $this->getAvailableQuantity($itemType, $itemId) >= $requiredQuantity;
    }
    public function getAvailableBatches(string $type, int $id, float $requiredQuantity): Collection
    {
        return InventoryBatch::where('item_type', $type)
            ->where('item_id', $id)
            ->where('quantity', '>', 0)
            ->orderBy('received_date', 'asc')
            ->get();
    }
}
