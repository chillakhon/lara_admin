<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadType;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\DeliveryMethod;
use App\Models\OrderDiscount;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * @SWG\Get(
     *     path="/api/orders",
     *     summary="Get a list of orders",
     *     tags={"Orders"},
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         type="string",
     *         description="Filter by order status"
     *     ),
     *     @SWG\Parameter(
     *         name="search",
     *         in="query",
     *         type="string",
     *         description="Search by order number or client name"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="List of orders",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Order")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Order::with([
            'client.user.profile',
            'items.product',
            'items.productVariant'
        ])->withCount('items');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client.user.profile', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(15)->through(function($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'discount_amount' => $order->discount_amount,
                'items_count' => $order->items_count,
                'created_at' => $order->created_at,
                'client' => $order->client ? [
                    'id' => $order->client->id,
                    'full_name' => $order->client->user->profile->full_name,
                    'email' => $order->client->user->email,
                    'phone' => $order->client->phone,
                ] : null,
                'items' => $order->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image' => $item->product->getFirstMediaUrl('images'),
                        ],
                        'variant' => $item->productVariant,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                }),
            ];
        });

        return response()->json([
            'orders' => $orders,
            'filters' => $request->only(['status', 'search']),
        ]);
    }


    /**
     * @SWG\Post(
     *     path="/api/orders",
     *     summary="Create a new order",
     *     tags={"Orders"},
     *     @SWG\Parameter(
     *         name="client_id",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="items",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="object",
     *                 @SWG\Property(property="product_id", type="integer"),
     *                 @SWG\Property(property="variant_id", type="integer"),
     *                 @SWG\Property(property="quantity", type="integer"),
     *                 @SWG\Property(property="price", type="number")
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response=201,
     *         description="Order created",
     *         @SWG\Schema(ref="#/definitions/Order")
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Invalid input"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:new,processing,completed,cancelled',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order = Order::create([
            'client_id' => $validated['client_id'],
            'order_number' => 'ORD-' . date('Ymd') . '-' . random_int(1000, 9999),
            'total_amount' => 0,
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['items'] as $item) {
            $order->items()->create($item);
        }

        $order->updateTotalAmount();

        $order->history()->create([
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'comment' => 'Заказ создан',
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order
        ], 201);
    }

    /**
     * @SWG\Get(
     *     path="/api/orders/{id}",
     *     summary="Get details of a specific order",
     *     tags={"Orders"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         description="ID of the order to retrieve"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Order details",
     *         @SWG\Schema(
     *             type="object",
     *             ref="#/definitions/Order"
     *         )
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function show(Order $order)
    {
        $order->load([
            'client.user.profile',
            'items.product',
            'items.productVariant',
            'history.user'
        ]);

        // Формируем данные для ответа в формате JSON
        $orderData = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_amount' => $order->total_amount,
            'discount_amount' => $order->discount_amount,
            'created_at' => $order->created_at,
            'notes' => $order->notes,
            'client' => $order->client ? [
                'id' => $order->client->id,
                'full_name' => $order->client->user->profile->full_name,
                'email' => $order->client->user->email,
                'phone' => $order->client->phone,
            ] : null,
            'items' => $order->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'image' => $item->product->getFirstMediaUrl('images'),
                    ],
                    'variant' => $item->productVariant,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            }),
            'history' => $order->history->map(function($record) {
                return [
                    'id' => $record->id,
                    'status' => $record->status,
                    'payment_status' => $record->payment_status,
                    'comment' => $record->comment,
                    'user' => $record->user ? [
                        'name' => $record->user->profile->full_name,
                    ] : null,
                    'created_at' => $record->created_at,
                ];
            }),
        ];

        return response()->json([
            'order' => $orderData,
            'statuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
        ]);
    }


    /**
     * @SWG\Put(
     *     path="/api/orders/{id}",
     *     summary="Update the details of a specific order",
     *     tags={"Orders"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         description="ID of the order to update"
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="string", enum={"new", "processing", "completed", "cancelled"}),
     *             @SWG\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}),
     *             @SWG\Property(property="notes", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @SWG\Schema(
     *             type="object",
     *             ref="#/definitions/Order"
     *         )
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:new,processing,completed,cancelled',
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        // Обновление данных заказа
        $order->update($validated);

        // Добавляем запись в историю
        $order->history()->create([
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'comment' => 'Заказ обновлен',
            'user_id' => auth()->id(),
        ]);

        // Возвращаем данные обновленного заказа в формате JSON
        return response()->json([
            'message' => 'Заказ успешно обновлен',
            'order' => $order,
        ]);
    }

    /**
     * @SWG\Delete(
     *     path="/api/orders/{id}",
     *     summary="Delete a specific order",
     *     tags={"Orders"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         type="integer",
     *         description="ID of the order to delete"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Order successfully deleted"
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function destroy(Order $order)
    {
        // Удаляем заказ
        $order->delete();

        // Возвращаем успешный ответ в формате JSON
        return response()->json([
            'message' => 'Заказ успешно удален',
            'order_id' => $order->id,
        ]);
    }

}
