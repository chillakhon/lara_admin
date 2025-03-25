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
     *     path="/api/orders",
     *     summary="Получить список заказов",
     *     description="Возвращает список заказов с фильтрами и пагинацией",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Статус заказа",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Поиск по номеру заказа или клиенту",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список заказов",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="order_number", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="payment_status", type="string"),
     *                 @OA\Property(property="is_paid", type="boolean"),
     *                 @OA\Property(property="total_amount", type="string"),
     *                 @OA\Property(property="discount_amount", type="string"),
     *                 @OA\Property(property="items_count", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="full_name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="phone", type="string")
     *                 ),
     *                 @OA\Property(property="items", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="product", type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="image", type="string")
     *                         ),
     *                         @OA\Property(property="variant", type="object"),
     *                         @OA\Property(property="quantity", type="integer"),
     *                         @OA\Property(property="price", type="string")
     *                     )
     *                 ),
     *                 @OA\Property(property="delivery_date", type="string", format="date-time"),
     *                 @OA\Property(property="delivery_method", type="object",
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="type", type="string")
     *                 ),
     *                 @OA\Property(property="delivery_target", type="string"),
     *                 @OA\Property(property="delivery_target_id", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка запроса"
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $query = Order::with([
            'client.user.profile' => function($query) {
            },
            'items.product.media', // Жадная загрузка медиа
            'items.productVariant',
            'deliveryMethod',
            'deliveryDate',
            'deliveryTarget'
        ])->withCount('items');

        // Фильтрация по статусу
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Поиск
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

        // Форматирование заказов с защитой от null
        $orders = $query->latest()
            ->paginate(15)
            ->through(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'is_paid' => $order->payment_status === 'paid',
                    'total_amount' => number_format($order->total_amount, 2, '.', ' ') . ' руб',
                    'discount_amount' => number_format($order->discount_amount, 2, '.', ' ') . ' руб',
                    'items_count' => $order->items_count,
                    'created_at' => optional($order->created_at)->format('d.m.Y H:i'),

                    // Безопасное получение клиента
                    'client' => optional($order->client, function($client) {
                        return [
                            'id' => $client->id,
                            'full_name' => optional(optional($client->user)->profile)->full_name ?? 'Не указано',
                            'email' => optional($client->user)->email ?? 'Не указано',
                            'phone' => $client->phone ?? 'Не указано',
                        ];
                    }),

                    // Безопасное получение товаров
                    'items' => $order->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'product' => optional($item->product, function($product) {
                                return [
                                    'id' => $product->id,
                                    'name' => $product->name,
                                    'image' => $product->getFirstMediaUrl('images') ?: null,
                                ];
                            }),
                            'variant' => $item->productVariant,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                        ];
                    }),

                    // Безопасное получение данных доставки
                    'delivery_date' => optional(optional($order->deliveryDate)->date)->format('d.m.Y H:i'),
                    'delivery_method' => optional($order->deliveryMethod, function($method) {
                        return [
                            'name' => $method->name,
                            'description' => $method->description,
                            'type' => $method->type,
                        ];
                    }),
                    'delivery_target' => optional($order->deliveryTarget)->name,
                    'delivery_target_id' => optional($order->deliveryTarget)->id,  // Добавленное поле
                ];
            });

        // Дополнительные данные для UI
        $additionalData = [
            'filters' => $request->only(['status', 'search']),
            'clients' => Client::with('user.profile')
                ->get()
                ->map(function($client) {
                    return [
                        'id' => $client->id,
                        'full_name' => optional(optional($client->user)->profile)->full_name ?? 'Клиент удален',
                        'email' => optional($client->user)->email ?? 'Не указан',
                        'phone' => $client->phone ?? 'Не указан',
                    ];
                }),
            'products' => Product::with(['variants'])
                ->where('is_active', true)
                ->get(),
            'statuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
        ];

        return response()->json(array_merge(
            ['orders' => $orders],
            $additionalData
        ));
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
     *         description="Детали заказа",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="order_number", type="string", example="ORD-20250313-1234"),
     *             @OA\Property(property="status", type="string", example="new"),
     *             @OA\Property(property="payment_status", type="string", example="pending"),
     *             @OA\Property(property="total_amount", type="number", example=5999.99),
     *             @OA\Property(property="discount_amount", type="number", example=1000),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-13T12:34:56Z"),
     *             @OA\Property(property="notes", type="string", example="Доставить до 18:00"),
     *             @OA\Property(property="delivery_date", type="string", format="date-time", example="2025-03-18T15:00:00Z"),
     *             @OA\Property(property="delivery_method", type="object",
     *                 @OA\Property(property="name", type="string", example="Курьерская доставка"),
     *                 @OA\Property(property="description", type="string", example="Доставка курьером до двери")
     *             ),
     *             @OA\Property(property="client", type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="full_name", type="string", example="Иван Иванов"),
     *                 @OA\Property(property="email", type="string", example="ivan@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+79161234567")
     *             ),
     *             @OA\Property(property="items", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="product", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Медицинская форма"),
     *                     @OA\Property(property="image", type="string", example="https://example.com/image.jpg")
     *                 ),
     *                 @OA\Property(property="variant", type="object", nullable=true),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="price", type="number", example=2999.99)
     *             )),
     *             @OA\Property(property="history", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="new"),
     *                 @OA\Property(property="payment_status", type="string", example="pending"),
     *                 @OA\Property(property="comment", type="string", example="Заказ создан"),
     *                 @OA\Property(property="user", type="object", nullable=true,
     *                     @OA\Property(property="name", type="string", example="Менеджер Иван")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-13T12:34:56Z")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Заказ не найден"),
     *     @OA\Response(response=500, description="Ошибка сервера")
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
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'discount_amount' => $order->discount_amount,
                'created_at' => $order->created_at,
                'notes' => $order->notes,
                'delivery_date' => $order->delivery_date, // Добавляем дату доставки
                'delivery_method' => $order->deliveryMethod,
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
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
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
