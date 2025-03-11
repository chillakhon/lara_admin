<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/orders",
     *     operationId="getOrders",
     *     tags={"Orders"},
     *     summary="Get list of orders",
     *     description="Retrieve a paginated list of orders with filtering and searching options.",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter orders by status (e.g., 'pending', 'completed'). Use 'all' to fetch all orders.",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search orders by order number or client name.",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="orders", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD123456"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="payment_status", type="string", example="paid"),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=99.99),
     *                 @OA\Property(property="discount_amount", type="number", format="float", example=5.00),
     *                 @OA\Property(property="items_count", type="integer", example=3),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:34:56Z"),
     *                 @OA\Property(property="client", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="phone", type="string", example="+1234567890")
     *                 ),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="product", type="object",
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="name", type="string", example="Product Name"),
     *                         @OA\Property(property="image", type="string", example="https://example.com/image.jpg")
     *                     ),
     *                     @OA\Property(property="variant", type="object", nullable=true),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="price", type="number", format="float", example=49.99)
     *                 ))
     *             ))
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
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client.user.profile', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()
            ->paginate(15)
            ->through(function ($order) {
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
                    'items' => $order->items->map(function ($item) {
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
        ], Response::HTTP_OK);
    }


    /**
     * @OA\Post(
     *     path="/orders",
     *     operationId="createOrder",
     *     tags={"Orders"},
     *     summary="Create a new order",
     *     description="Create a new order with associated items and return the created order.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"client_id", "items", "status", "payment_status"},
     *             @OA\Property(property="client_id", type="integer", example=1, description="ID of the client"),
     *             @OA\Property(property="items", type="array", description="List of order items",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"product_id", "quantity", "price"},
     *                     @OA\Property(property="product_id", type="integer", example=10, description="Product ID"),
     *                     @OA\Property(property="variant_id", type="integer", nullable=true, example=5, description="Variant ID (optional)"),
     *                     @OA\Property(property="quantity", type="number", format="float", example=2, description="Quantity of product"),
     *                     @OA\Property(property="price", type="number", format="float", example=49.99, description="Price per unit")
     *                 )
     *             ),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Urgent delivery", description="Additional order notes"),
     *             @OA\Property(property="status", type="string", enum={"new", "processing", "completed", "cancelled"}, example="new", description="Order status"),
     *             @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}, example="pending", description="Payment status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Order successfully created"),
     *             @OA\Property(property="order", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20250310-1234"),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=99.98),
     *                 @OA\Property(property="status", type="string", example="new"),
     *                 @OA\Property(property="payment_status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:34:56Z"),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="product_id", type="integer", example=10),
     *                     @OA\Property(property="variant_id", type="integer", nullable=true, example=5),
     *                     @OA\Property(property="quantity", type="number", format="float", example=2),
     *                     @OA\Property(property="price", type="number", format="float", example=49.99)
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
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
            'notes' => $validated['notes'] ?? null,
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
            'message' => 'Заказ успешно создан',
            'order' => $order->load('items'),
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/orders/{order}",
     *     operationId="getOrder",
     *     tags={"Orders"},
     *     summary="Get order details",
     *     description="Retrieve detailed information about a specific order.",
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID of the order",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="order_number", type="string", example="ORD-20250310-1234"),
     *             @OA\Property(property="status", type="string", example="new"),
     *             @OA\Property(property="payment_status", type="string", example="pending"),
     *             @OA\Property(property="total_amount", type="number", format="float", example=99.98),
     *             @OA\Property(property="discount_amount", type="number", format="float", example=10.00),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:34:56Z"),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Urgent delivery"),
     *             @OA\Property(property="client", type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+123456789")
     *             ),
     *             @OA\Property(property="items", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="product", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Medical Gloves"),
     *                     @OA\Property(property="image", type="string", example="https://example.com/image.jpg")
     *                 ),
     *                 @OA\Property(property="variant", type="object",
     *                     @OA\Property(property="id", type="integer", example=15),
     *                     @OA\Property(property="name", type="string", example="Size M")
     *                 ),
     *                 @OA\Property(property="quantity", type="number", format="float", example=2),
     *                 @OA\Property(property="price", type="number", format="float", example=49.99)
     *             )),
     *             @OA\Property(property="history", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="processing"),
     *                 @OA\Property(property="payment_status", type="string", example="paid"),
     *                 @OA\Property(property="comment", type="string", example="Updated by admin"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="Admin User")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:34:56Z")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Order not found")
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

        return response()->json([
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
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'image' => $item->product->getFirstMediaUrl('images'),
                    ],
                    'variant' => $item->productVariant ? [
                        'id' => $item->productVariant->id,
                        'name' => $item->productVariant->name,
                    ] : null,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            }),
            'history' => $order->history->map(function ($record) {
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
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Put(
     *     path="/orders/{order}",
     *     operationId="updateOrder",
     *     tags={"Orders"},
     *     summary="Update an order",
     *     description="Update the status and payment status of an order.",
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID of the order",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status", "payment_status"},
     *             @OA\Property(property="status", type="string", example="processing", enum={"new", "processing", "completed", "cancelled"}),
     *             @OA\Property(property="payment_status", type="string", example="paid", enum={"pending", "paid", "failed", "refunded"}),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Customer requested urgent delivery")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Order updated successfully"),
     *             @OA\Property(property="order", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="processing"),
     *                 @OA\Property(property="payment_status", type="string", example="paid"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example="Customer requested urgent delivery"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-10T12:45:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:new,processing,completed,cancelled',
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        // Добавляем запись в историю
        $order->history()->create([
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'comment' => 'Заказ обновлен',
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'notes' => $order->notes,
                'updated_at' => $order->updated_at,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/orders/{order}",
     *     operationId="deleteOrder",
     *     tags={"Orders"},
     *     summary="Delete an order",
     *     description="Deletes a specific order by its ID.",
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID of the order",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Order deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function destroy(Order $order)
    {
        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully'
        ], Response::HTTP_OK);
    }

}
