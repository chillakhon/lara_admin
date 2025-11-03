<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryBalanceResource;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Product;
use App\Models\Unit;
use App\Services\InventoryService;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InventoryController extends Controller
{
    use HelperTrait;
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Получение списка материалов, продуктов и их остатков
     *
     * Этот метод возвращает данные об инвентаре, включая материалы, продукты, единицы измерения
     * и их остатки на складе.
     *
     * @OA\Get(
     *     path="/api/inventory",
     *     tags={"Inventory"},
     *     summary="Получение списка материалов, продуктов и их остатков",
     *     description="Возвращает список материалов, продуктов, единиц измерения и их инвентарных остатков.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="materials", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string")
     *             )),
     *             @OA\Property(property="products", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="has_variants", type="boolean"),
     *                 @OA\Property(property="variants", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="sku", type="string"),
     *                     @OA\Property(property="unit_id", type="integer"),
     *                     @OA\Property(property="inventoryBalance", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="item_id", type="integer"),
     *                         @OA\Property(property="item_type", type="string"),
     *                         @OA\Property(property="total_quantity", type="number", format="float"),
     *                         @OA\Property(property="average_price", type="number", format="float"),
     *                         @OA\Property(property="unit", type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string")
     *                         )
     *                     )
     *                 ))
     *             )),
     *             @OA\Property(property="units", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string")
     *             )),
     *             @OA\Property(property="materialsInventory", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="item", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     @OA\Property(property="unit", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 ))
     *             ),
     *             @OA\Property(property="productsInventory", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="item", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="has_variants", type="boolean")
     *                     ),
     *                     @OA\Property(property="unit", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $inventoryBalances = InventoryBalance::with('item')->paginate(10);


        foreach ($inventoryBalances as $key => &$inventory_item) {
            $inventory_item->item_type = $this->get_type_by_model($inventory_item->item_type);
        }

        return response()->json($inventoryBalances);

        // return response()->json([
        //     'materials' => Material::select('id', 'title')->get(),
        //     'products' => Product::select('id', 'name', 'has_variants')
        //         ->with([
        //             'variants' => function ($query) {
        //                 $query->select('id', 'product_id', 'name', 'sku', 'unit_id')
        //                     ->with([
        //                         'inventoryBalance' => function ($q) {
        //                             $q->select('id', 'item_id', 'item_type', 'total_quantity', 'average_price', 'unit_id')
        //                                 ->with('unit:id,name');
        //                         }
        //                     ]);
        //             }
        //         ])
        //         ->get(),
        //     'units' => Unit::select('id', 'name')->get(),
        //     'materialsInventory' => InventoryBalance::where('item_type', 'material')
        //         ->with(['item:id,title as name', 'unit:id,name'])
        //         ->paginate(10),
        //     'productsInventory' => InventoryBalance::where('item_type', 'product')
        //         ->with([
        //             'item' => function ($query) {
        //                 $query->select('id', 'name', 'has_variants')
        //                     ->with([
        //                         'variants' => function ($q) {
        //                             $q->select('id', 'product_id', 'name', 'sku', 'unit_id')
        //                                 ->with([
        //                                     'inventoryBalance' => function ($bal) {
        //                                         $bal->select('id', 'item_id', 'item_type', 'total_quantity', 'average_price', 'unit_id')
        //                                             ->with('unit:id,name');
        //                                     }
        //                                 ]);
        //                         }
        //                     ]);
        //             },
        //             'unit:id,name'
        //         ])
        //         ->paginate(10)
        // ]);
    }

    /**
     * Добавление товара на склад
     *
     * @OA\Post(
     *     path="/api/inventory/add",
     *     tags={"Inventory"},
     *     summary="Добавить товар на склад",
     *     description="Добавляет материалы, продукты или их варианты на склад с указанием цены, количества и даты получения.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"item_type", "item_id", "quantity", "price_per_unit", "unit_id", "received_date"},
     *             @OA\Property(property="item_type", type="string", enum={"material", "product", "variant"}),
     *             @OA\Property(property="item_id", type="integer"),
     *             @OA\Property(property="quantity", type="number", format="float", minimum=0),
     *             @OA\Property(property="price_per_unit", type="number", format="float", minimum=0),
     *             @OA\Property(property="unit_id", type="integer"),
     *             @OA\Property(property="received_date", type="string", format="date"),
     *             @OA\Property(property="description", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Запас успешно добавлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Запас успешно добавлен"),
     *             @OA\Property(property="batch", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Ошибка валидации"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     */
    public function addStock(Request $request)
    {
        $validated = $request->validate([
            'items.*.item_type' => 'required|in:Material,Product,ProductVariant',
            'items.*.item_id' => 'required|integer',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.old_price' => 'nullable|numeric|min:0',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.barcode' => 'nullable|string',
        ]);

        try {
            $updates = [];

            foreach ($validated['items'] as $item) {
                $updates[] = $this->inventoryService->addStock(
                    $item['item_type'],
                    $item['item_id'],
                    $item['price'],
                    $item['cost_price'],
                    $item['old_price'],
                    $item['quantity'],
                    $item['barcode'],
                );
            }


            return response()->json([
                'message' => 'Запас успешно добавлен',
                'batch' => $updates
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Удаление товара со склада
     *
     * @OA\Post(
     *     path="/api/inventory/remove",
     *     tags={"Inventory"},
     *     summary="Списать товар со склада",
     *     description="Списывает материалы, продукты или их варианты со склада.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"item_type", "item_id", "quantity"},
     *             @OA\Property(property="item_type", type="string", enum={"material", "product", "variant"}),
     *             @OA\Property(property="item_id", type="integer"),
     *             @OA\Property(property="quantity", type="number", format="float", minimum=0),
     *             @OA\Property(property="description", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Запас успешно списан",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Запас успешно списан")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Ошибка валидации"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     */
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

            return response()->json([
                'message' => 'Запас успешно списан'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

    /**
     * @OA\Get(
     *     path="/api/inventory/transactions/history",
     *     summary="Получить историю транзакций",
     *     tags={"Inventory"},
     *     @OA\Parameter(
     *         name="item_type",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", enum={"material", "product", "variant"})
     *     ),
     *     @OA\Parameter(
     *         name="item_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(response=200, description="История транзакций"),
     *     @OA\Response(response=400, description="Ошибка запроса")
     * )
     */
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

    /**
     * Получение списка транзакций склада
     *
     * @OA\Get(
     *     path="/api/inventory/transactions",
     *     tags={"Inventory"},
     *     summary="Получить список транзакций",
     *     description="Возвращает список всех складских транзакций с пагинацией.",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список транзакций",
     *         @OA\JsonContent(
     *             @OA\Property(property="transactions", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="item", type="object", nullable=true),
     *                 @OA\Property(property="unit", type="object", nullable=true),
     *                 @OA\Property(property="user", type="object", nullable=true),
     *                 @OA\Property(property="quantity", type="number"),
     *                 @OA\Property(property="price_per_unit", type="number"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     */
    public function transactions()
    {
        try {
            $transactions = InventoryTransaction::with('item', 'unit', 'user')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total' => $transactions->total(),
                    'per_page' => $transactions->perPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
