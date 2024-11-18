<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryBalanceResource;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Product;
use App\Models\Unit;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index()
    {
        return Inertia::render('Dashboard/Inventory/Index', [
            'materials' => Material::select('id', 'title')->get(),
            'products' => Product::select('id', 'name', 'has_variants')
                ->with(['variants' => function($query) {
                    $query->select('id', 'product_id', 'name', 'sku', 'unit_id')
                        ->with(['inventoryBalance' => function($q) {
                            $q->select('id', 'item_id', 'item_type', 'total_quantity', 'average_price', 'unit_id')
                                ->with('unit:id,name');
                        }]);
                }])
                ->get(),
            'units' => Unit::select('id', 'name')->get(),
            'materialsInventory' => InventoryBalance::where('item_type', 'material')
                ->with(['item:id,title as name', 'unit:id,name'])
                ->paginate(10),
            'productsInventory' => InventoryBalance::where('item_type', 'product')
                ->with(['item' => function($query) {
                    $query->select('id', 'name', 'has_variants')
                        ->with(['variants' => function($q) {
                            $q->select('id', 'product_id', 'name', 'sku', 'unit_id')
                                ->with(['inventoryBalance' => function($bal) {
                                    $bal->select('id', 'item_id', 'item_type', 'total_quantity', 'average_price', 'unit_id')
                                        ->with('unit:id,name');
                                }]);
                        }]);
                }, 'unit:id,name'])
                ->paginate(10)
        ]);
    }


    public function addStock(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:material,product,variant',
            'item_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
            'received_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        try {
            $batch = $this->inventoryService->addStock(
                $validated['item_type'],
                $validated['item_id'],
                $validated['quantity'],
                $validated['price_per_unit'],
                $validated['unit_id'],
                $validated['received_date'],
                Auth::id(),
                $validated['description'] ?? null
            );

            return redirect()->back()->with('success', 'Запас успешно добавлен');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function removeStock(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:material,product,variant',
            'item_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        try {
            $this->inventoryService->removeStock(
                $validated['item_type'],
                $validated['item_id'],
                $validated['quantity'],
                Auth::id(),
                $validated['description'] ?? null
            );

            return redirect()->back()->with('success', 'Запас успешно списан');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function getStock(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:material,product,variant',
            'item_id' => 'required|integer',
        ]);

        try {
            $stock = $this->inventoryService->getStock(
                $validated['item_type'],
                $validated['item_id']
            );

            return response()->json($stock);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function getTransactionHistory(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:material,product,variant',
            'item_id' => 'required|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        try {
            $history = $this->inventoryService->getTransactionHistory(
                $validated['item_type'],
                $validated['item_id'],
                $validated['from_date'] ?? null,
                $validated['to_date'] ?? null
            );

            return response()->json($history);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function transactions()
    {
        $transactions = InventoryTransaction::with('item', 'unit', 'user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return Inertia::render('Dashboard/Inventory/Transactions', [
            'transactions' => $transactions,
        ]);
    }
}
