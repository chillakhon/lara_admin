<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Client;
use App\Models\Product;
use App\Models\DeliveryDate;
use App\Models\DeliveryMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/orders",
     *     summary="Получить список заказов",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Фильтр по статусу заказа (если 'all', то фильтр не применяется)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Поиск по номеру заказа или имени клиента",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список заказов",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="orders", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20250325-1234"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="payment_status", type="string", example="paid"),
     *                 @OA\Property(property="is_paid", type="boolean", example=true),
     *                 @OA\Property(property="total_amount", type="string", example="1500.00 руб"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="25.03.2025 14:30"),
     *                 @OA\Property(property="client", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="full_name", type="string", example="Иван Иванов")
     *                 ),
     *                 @OA\Property(property="delivery_method", type="object", nullable=true,
     *                     @OA\Property(property="name", type="string", example="Курьер"),
     *                     @OA\Property(property="type", type="string", example="express")
     *                 )
     *             )),
     *             @OA\Property(property="filters", type="object",
     *                 @OA\Property(property="status", type="string", nullable=true, example="pending"),
     *                 @OA\Property(property="search", type="string", nullable=true, example="ORD-20250325-1234")
     *             ),
     *             @OA\Property(property="clients", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="full_name", type="string", example="Иван Иванов")
     *             )),
     *             @OA\Property(property="statuses", type="array", @OA\Items(type="string", example="pending")),
     *             @OA\Property(property="paymentStatuses", type="array", @OA\Items(type="string", example="paid"))
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        $query = Order::with([
            'client.user.profile',
            // 'items.product',
            // 'items.productVariant',
            'deliveryMethod',
            // 'deliveryDate',
            // 'deliveryTarget'
        ]);

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

        $orders = $query->latest()
            ->paginate(15)
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'is_paid' => $order->payment_status === 'paid',
                    'total_amount' => number_format($order->total_amount, 2, '.', ' ') . ' руб',
                    // 'discount_amount' => number_format($order->discount_amount, 2, '.', ' ') . ' руб',
                    // 'items_count' => $order->items_count,
                    'created_at' => $order->created_at->format('d.m.Y H:i'),
                    'client' => $order->client ? [
                        'id' => $order->client->id,
                        'full_name' => $order->client->user->profile->full_name,
                        // 'email' => $order->client->user->email,
                        // 'phone' => $order->client->phone,
                    ] : null,
                    /*'items' => $order->items->map(function($item) {
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
                    }),*/
                    // 'delivery_date' => $order->deliveryDate ? $order->deliveryDate->date->format('d.m.Y H:i') : null,
                    'delivery_method' => $order->deliveryMethod ? [
                        'name' => $order->deliveryMethod->name,
                        // 'description' => $order->deliveryMethod->description,
                        'type' => $order->deliveryMethod->type,
                    ] : null,
                    // 'delivery_target' => $order->deliveryTarget ? $order->deliveryTarget->name : null,
                ];
            });

        return response()->json([
            'orders' => $orders,
            'filters' => $request->only(['status', 'search']),
            'clients' => Client::with('user.profile')
                ->get()
                ->map(function($client) {
                    return [
                        // 'id' => $client->id,
                        'full_name' => $client->user->profile->full_name,
                        // 'email' => $client->user->email,
                        // 'phone' => $client->phone,
                    ];
                }),
            /*'products' => Product::with(['variants'])
                ->where('is_active', true)
                ->get(),*/
            'statuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Создание нового заказа",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"client_id", "items", "status", "payment_status", "delivery_method_id"},
     *             @OA\Property(property="client_id", type="integer", example=1),
     *             @OA\Property(property="items", type="array", @OA\Items(
     *                 type="object",
     *                 required={"product_id", "quantity", "price"},
     *                 @OA\Property(property="product_id", type="integer", example=10),
     *                 @OA\Property(property="variant_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="quantity", type="number", example=2),
     *                 @OA\Property(property="price", type="number", example=1999.99)
     *             )),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Доставить до 18:00"),
     *             @OA\Property(property="status", type="string", enum={"new", "processing", "completed", "cancelled"}, example="new"),
     *             @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}, example="pending"),
     *             @OA\Property(property="delivery_date", type="string", format="date-time", nullable=true, example="2025-03-18T15:00:00Z", description="Ожидаемая дата доставки"),
     *             @OA\Property(property="delivery_method_id", type="integer", example=3, description="ID метода доставки"),
     *             @OA\Property(property="delivery_target_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="data", type="string", nullable=true, example="Some additional data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Заказ успешно создан",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Заказ успешно создан"),
     *             @OA\Property(property="order", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="order_number", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="payment_status", type="string"),
     *                 @OA\Property(property="total_amount", type="number"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="delivery_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="delivery_method_id", type="integer", example=3, description="ID метода доставки"),
     *                 @OA\Property(property="delivery_target_id", type="integer", nullable=true),
     *                 @OA\Property(property="data", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Ошибка валидации"),
     *     @OA\Response(response=500, description="Ошибка сервера")
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
            'status' => 'required|in:' . implode(',', array_column(Order::STATUSES, 'value')),
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'delivery_date' => 'nullable|date_format:Y-m-d H:i:s',
            'delivery_method_id' => 'required|exists:delivery_methods,id',
            'delivery_target_id' => 'nullable|exists:delivery_targets,id',
            'data' => 'nullable|string',
        ]);

        try {
            $order = Order::create([
                'client_id' => $validated['client_id'],
                'order_number' => 'ORD-' . date('Ymd') . '-' . random_int(1000, 9999),
                'total_amount' => 0,
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
                'notes' => $validated['notes'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'delivery_method_id' => $validated['delivery_method_id'] ?? null,
                'delivery_target_id' => $validated['delivery_target_id'] ?? null,
                'data' => $validated['data'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create($item);
            }

            $order->updateTotalAmount();

            return response()->json([
                'message' => 'Заказ успешно создан',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at,
                    'delivery_date' => $order->delivery_date,
                    'delivery_method_id' => $order->delivery_method_id,
                    'delivery_method_name' => $order->deliveryMethod ? $order->deliveryMethod->name : null,
                    'delivery_target_id' => $order->delivery_target_id, // Возвращаем delivery_target_id
                    'data' => $order->data,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{order}",
     *     summary="Получение информации о заказе",
     *     description="Возвращает детальную информацию о заказе, включая товары, клиента, доставку и историю изменений.",
     *     operationId="showOrder",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=123),
     *             @OA\Property(property="order_number", type="string", example="ORD-2025-0001"),
     *             @OA\Property(property="status", type="string", example="processing"),
     *             @OA\Property(property="payment_status", type="string", example="paid"),
     *             @OA\Property(property="total_amount", type="number", format="float", example=1500.50),
     *             @OA\Property(property="discount_amount", type="number", format="float", example=100.00),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="26.03.2025 12:45"),
     *             @OA\Property(property="notes", type="string", example="Позвонить перед доставкой"),
     *
     *             @OA\Property(property="delivery_date", type="string", format="date", example="2025-03-30"),
     *             @OA\Property(property="delivery_method", type="object",
     *                 @OA\Property(property="name", type="string", example="Курьерская доставка"),
     *                 @OA\Property(property="description", type="string", example="Доставка в течение 2-3 дней"),
     *                 @OA\Property(property="type", type="string", example="express")
     *             ),
     *             @OA\Property(property="delivery_target", type="string", example="Квартира"),
     *
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="full_name", type="string", example="Иван Иванов"),
     *                 @OA\Property(property="email", type="string", example="ivan@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+79995554433"),
     *                 @OA\Property(property="address", type="string", example="г. Москва, ул. Ленина, д. 10, кв. 15")
     *             ),
     *
     *             @OA\Property(property="items", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=101),
     *                     @OA\Property(property="product", type="object",
     *                         @OA\Property(property="id", type="integer", example=55),
     *                         @OA\Property(property="name", type="string", example="Медицинский халат"),
     *                         @OA\Property(property="image", type="string", format="url", example="https://example.com/images/product1.jpg"),
     *                         @OA\Property(property="article", type="string", example="ART-123456")
     *                     ),
     *                     @OA\Property(property="variant", type="string", example="XL, Белый"),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="price", type="number", format="float", example=750.25),
     *                     @OA\Property(property="reserve", type="integer", example=1)
     *                 )
     *             ),
     *
     *             @OA\Property(property="history", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="status", type="string", example="shipped"),
     *                     @OA\Property(property="payment_status", type="string", example="paid"),
     *                     @OA\Property(property="comment", type="string", example="Отправлен в службу доставки"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="name", type="string", example="Менеджер Иван")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="26.03.2025 13:15")
     *                 )
     *             ),
     *
     *             @OA\Property(property="payment_details", type="object",
     *                 @OA\Property(property="transaction_number", type="string", example="TXN-987654"),
     *                 @OA\Property(property="payment_type", type="string", example="Карта")
     *             ),
     *
     *             @OA\Property(property="delivery_details", type="object",
     *                 @OA\Property(property="address", type="string", example="г. Москва, ул. Ленина, д. 10"),
     *                 @OA\Property(property="pickup_point", type="string", example="Пункт выдачи №123"),
     *                 @OA\Property(property="delivery_cost", type="number", format="float", example=300.00)
     *             ),
     *
     *             @OA\Property(property="technical_details", type="object",
     *                 @OA\Property(property="army_card", type="string", example="12345-ABC"),
     *                 @OA\Property(property="black_price", type="number", format="float", example=5000.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Ошибка сервера"),
     *             @OA\Property(property="message", type="string", example="Текст ошибки")
     *         )
     *     )
     * )
     */

    public function show(Order $order)
    {
        try {
            $order->load([
                'client.user.profile',
                'items.product',
                'items.productVariant',
                'history.user',
                'deliveryMethod'
            ]);

            return response()->json([
                // Основная информация о заказе
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'discount_amount' => $order->discount_amount,
                'created_at' => $order->created_at->format('d.m.Y H:i'),
                'notes' => $order->notes,

                // Информация о доставке
                'delivery_date' => $order->delivery_date,
                'delivery_method' => $order->deliveryMethod ? [
                    'name' => $order->deliveryMethod->name,
                    'description' => $order->deliveryMethod->description,
                    'type' => $order->deliveryMethod->type,
                ] : null,
                'delivery_target' => $order->deliveryTarget ? $order->deliveryTarget->name : null,

                // Информация о клиенте
                'client' => $order->client ? [
                    'id' => $order->client->id,
                    'full_name' => $order->client->user->profile->full_name,
                    'email' => $order->client->user->email,
                    'phone' => $order->client->phone,
                    'address' => $order->client->address // добавлено
                ] : null,

                // Товары в заказе
                'items' => $order->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product' => $item->product ?[
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image' => $item->product->getFirstMediaUrl('images'),
                            'article' => $item->product->article // добавлено
                        ]: null,
                        'variant' => $item->productVariant,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'reserve' => $item->reserve_quantity // добавлено
                    ];
                }),

                // История изменений
                'history' => $order->history->map(function($record) {
                    return [
                        'id' => $record->id,
                        'status' => $record->status,
                        'payment_status' => $record->payment_status,
                        'comment' => $record->comment,
                        'user' => $record->user ? [
                            'name' => $record->user->profile->full_name,
                        ] : null,
                        'created_at' => $record->created_at->format('d.m.Y H:i'),
                    ];
                }),

                // Новые блоки из картинки
                'payment_details' => [ // добавлено
                    'transaction_number' => $order->transaction_number,
                    'payment_type' => $order->payment_type
                ],
                'delivery_details' => [ // добавлено
                    'address' => $order->delivery_address,
                    'pickup_point' => $order->pickup_point_name,
                    'delivery_cost' => $order->delivery_cost
                ],
                'technical_details' => [ // добавлено
                    'army_card' => $order->army_card_info,
                    'black_price' => $order->black_price
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка сервера',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/orders/{order}",
     *     summary="Обновление заказа",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status", "payment_status"},
     *             @OA\Property(property="status", type="string", enum={"new", "processing", "completed", "cancelled"}, example="processing"),
     *             @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}, example="paid"),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Обновлен менеджером"),
     *             @OA\Property(property="delivery_date", type="string", format="date-time", nullable=true, example="2025-03-15T12:00:00Z"),
     *             @OA\Property(property="delivery_method_id", type="integer", nullable=true, example=3, description="ID метода доставки"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно обновлен",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Заказ успешно обновлен"),
     *             @OA\Property(property="order", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="processing"),
     *                 @OA\Property(property="payment_status", type="string", example="paid"),
     *                 @OA\Property(property="notes", type="string", example="Обновлен менеджером"),
     *                 @OA\Property(property="delivery_date", type="string", format="date-time", example="2025-03-15T12:00:00Z"),
     *                @OA\Property(property="delivery_method_id", type="integer", nullable=true, example=3, description="ID метода доставки"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     */
    public function update(Request $request, Order $order)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:new,processing,completed,cancelled',
                'payment_status' => 'required|string|in:pending,paid,failed,refunded',
                'notes' => 'nullable|string',
                'delivery_date' => 'nullable|date_format:Y-m-d\TH:i:s\Z', // Валидация для даты доставки
                'delivery_method_id' => 'nullable|exists:delivery_methods,id',
                ]);

            $order->update([
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
                'notes' => $validated['notes'],
                'delivery_date' => $validated['delivery_date'], // Добавляем дату доставки,
                'delivery_method_id' => $validated['delivery_method_id'] ?? null, // Обновляем delivery_method_id
            ]);

            // Добавляем запись в историю
            $order->history()->create([
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
                'comment' => 'Заказ обновлен',
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Заказ успешно обновлен',
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'notes' => $order->notes,
                    'delivery_date' => $order->delivery_date, // Добавляем дату доставки
                    'delivery_method_id' => $order->delivery_method_id, // Добавляем метод доставки
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/orders/{order}",
     *     summary="Удаление заказа",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно удален",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Заказ успешно удален")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Заказ не найден"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     */

    public function destroy(Order $order)
    {
        try {
            $order->delete();
            return response()->json(['message' => 'Заказ успешно удален'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

}
