<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Client;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\DeliveryDate;
use App\Models\DeliveryMethod;
use App\Models\PromoCode;
use App\Models\Shipment;
use App\Models\UserProfile;
use App\Services\Delivery\CdekDeliveryService;
use App\Services\TelegramNotificationService;
use App\Services\WhatsappService;
use App\Traits\HelperTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;


class OrderController extends Controller
{

    use HelperTrait;

    public function getUserOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Пользователь не авторизован'], 401);
        }

        $client = Client::where('user_id', $user->id)->whereNull('deleted_at')->first();

        if (!$client) {
            return response()->json(['error' => 'Клиент не найден!'], 404);
        }

        $orders = Order::with([
            'items.product',
            'items.productVariant',
            'items.color' => function ($sql) {
                $sql->select(['id', 'name', 'code']);
            },
            // 'payments',
            'deliveryMethod',
            'deliveryTarget',
        ])
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function index(Request $request)
    {


        $perPage = (int)$request->input('per_page', 15);
        $page = $request->input('page', 1);


        $query = Order::with([
            'client.profile',
            // 'items.product',
            // 'items.productVariant',
            'deliveryMethod',
            // 'deliveryDate',
            // 'deliveryTarget'
        ]);


        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->input('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('client.profile', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }


        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->input('date_from'),
                $request->input('date_to'),
            ]);
        }


        $orders = $query->latest()
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
//                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'is_paid' => $order->payment_status === 'paid',
                    'total_amount' => number_format($order->total_amount, 2, '.', ' ') . ' руб',
                    // 'discount_amount' => number_format($order->discount_amount, 2, '.', ' ') . ' руб',
                    // 'items_count' => $order->items_count,
                    'created_at' => $order->created_at->format('d.m.Y H:i'),
                    'client' => $order->client ? [
                        'id' => $order->client->id,
                        'full_name' => $order->client?->profile->full_name,
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

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $this->validateOrderData($request);


        DB::beginTransaction();

        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    "success" => false,
                    "message" => "Клиент не найден!"
                ]);
            }

            $promo = null;
            if (!empty($validated['promo_code'])) {
                try {
                    $promo = $this->validatePromoCode($validated['promo_code'], $client);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage()
                    ], 422);
                }
            }

            $order = $this->createOrder($validated, $client->id);


            $result = $this->processOrderItems($order, $validated['items']);


            $totalDiscountAmount = $result['total_discount'];
            $orderTotalBefore = $result['order_total'];

            if ($promo) {
                $promoDiscount = $this->applyPromoCodeToOrder($order, $promo, $orderTotalBefore, $totalDiscountAmount);
                $totalDiscountAmount += $promoDiscount;
            } else {
                $order->update([
                    'discount_amount' => $totalDiscountAmount
                ]);
                $order->updateTotalAmount();
            }


            $this->sendNotifications($client, $order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно создан',
                'order' => $order->fresh()
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    private function validateOrderData(Request $request)
    {
        return $request->validate([
            'promo_code' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.color_id' => 'nullable|exists:colors,id',
            'items.*.discount_id' => 'nullable|exists:discounts,id',
            'notes' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'delivery_method_id' => 'nullable|exists:delivery_methods,id', // required
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'data' => 'nullable|string',
            // 'delivery_data' => 'required|array',
            // 'delivery_data.delivery_method_id' => 'required|exists:delivery_methods,id',
            // 'delivery_data.delivery_type_code' => 'required|string',
            'country_code' => 'required|string|size:2',
            'city_name' => 'required|string',
            'location' => 'nullable',
            'tariff' => 'nullable'
        ]);
    }

    private function validatePromoCode(?string $code, $client)
    {
        $promo = PromoCode::where('code', $code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (!$promo) {
            throw new \Exception('Купон недействителен или истёк.');
        }

        if ($promo->max_uses !== null && $promo->times_used >= $promo->max_uses) {
            throw new \Exception('Лимит использований купона исчерпан.');
        }

        $alreadyUsed = $promo->usages()
            ->where('client_id', $client->id)
            ->where('promo_code_id', $promo->id)
            ->exists();

        if ($alreadyUsed) {
            throw new \Exception('Вы уже использовали этот купон ранее.');
        }

        return $promo;
    }

    private function createOrder(array $validated, int $clientId)
    {
        return Order::create([
            'client_id' => $clientId,
            'order_number' => 'ORD-' . now()->format('Ymd-His') . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'total_amount' => 0,
            'status' => 'new',
            'payment_status' => 'pending',
            'notes' => $validated['notes'] ?? null,
            // 'delivery_method_id' => $validated['delivery_method_id'],
            'delivery_zone_id' => $validated['delivery_zone_id'] ?? null,
            'data' => $validated['data'] ?? null,
        ]);
    }

    private function processOrderItems(Order $order, array $items): array
    {
        $totalDiscountAmount = 0;
        $orderTotalBefore = 0;

        foreach ($items as $item) {
            $finalPrice = $item['price']; // уже со скидкой
            $discountAmount = 0;
            $originalPrice = $finalPrice; // по умолчанию считаем что скидки не было

            if (!empty($item['discount_id'])) {
                $discount = Discount::find($item['discount_id']);

                if ($discount && $discount->is_active) {
                    // Допустим, если есть скидка — то клиент передал `original_price` как (price + скидка)
                    // либо считаем old_price приходит в $item['original_price']
                    $originalPrice = $finalPrice; // можно заменить на $item['original_price'] если передаётся
                    if ($discount->type === 'fixed') {
                        $originalPrice = $finalPrice + $discount->value;
                    } elseif ($discount->type === 'percentage') {
                        $originalPrice = round($finalPrice / (1 - $discount->value / 100), 2);
                    }

                    $discountAmount = $originalPrice - $finalPrice;

                    OrderDiscount::create([
                        'order_id' => $order->id,
                        'discount_id' => $discount->id,
                        'discountable_type' => $item['product_variant_id'] ? 'product_variant' : 'product',
                        'discountable_id' => $item['product_variant_id'] ?? $item['product_id'],
                        'original_price' => $originalPrice,
                        'discount_amount' => $discountAmount,
                        'final_price' => $finalPrice,
                    ]);

                    $totalDiscountAmount += $discountAmount * $item['quantity'];
                }
            }

            $orderTotalBefore += $finalPrice * $item['quantity'];

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'],
                'color_id' => $item['color_id'],
                'quantity' => $item['quantity'],
                'price' => $finalPrice,
                'discount' => $discountAmount
            ]);
        }

        return [
            'total_discount' => $totalDiscountAmount,
            'order_total' => $orderTotalBefore,
        ];
    }

    private function applyPromoCodeToOrder(Order $order, PromoCode $promo, float $orderTotalBefore, float $existingDiscount): float
    {
        $promoDiscount = 0;

        if ($promo->discount_type === 'fixed') {
            $promoDiscount = min($promo->discount_amount, $orderTotalBefore);
        } elseif ($promo->discount_type === 'percentage') {
            $promoDiscount = round($orderTotalBefore * ($promo->discount_amount / 100), 2);
        }

        $promo->increment('times_used');

        $order->update([
            'promo_code_id' => $promo->id,
            'discount_amount' => $existingDiscount + $promoDiscount,
            'total_amount' => max(0, $orderTotalBefore - $promoDiscount)
        ]);

        return $promoDiscount;
    }

    private function createShipmentForOrder(Order $order, array $validated)
    {
        $deliveryMethodId = $validated['delivery_method_id'];
        $deliveryData = $validated;//json_decode($validated['data'] ?? '{}', true);


        $shipmentData = [
            'order_id' => $order->id,
            'delivery_method_id' => $deliveryMethodId,
            'status_id' => 1, // статус "new"
            'shipping_address' => $validated['delivery_address'] ?? '',
            'recipient_name' => $order->client?->get_full_name() ?? '',
            'recipient_phone' => $order->client?->profile?->phone ?? '',
            'cost' => 0
        ];

        if ($deliveryMethodId === 1) {
            if (empty($deliveryData['location_code'])) {
                throw new \Exception('Не указан код ПВЗ (location_code)');
            }

            $shipmentData = array_merge($shipmentData, [
                'location_code' => $deliveryData['location_code'],
                'city' => $deliveryData['city'] ?? null,
                'full_address' => $deliveryData['address'] ?? null,
                'tariff_code' => null
            ]);
        } elseif ($deliveryMethodId === 3) {
            $tariff = $deliveryData['tariff'] ?? null;

            if (!$tariff || empty($validated['delivery_address'])) {
                Log::info("about tariff", [$tariff, $validated['delivery_address']]);
                throw new \Exception('Для курьерской доставки нужно указать тариф и адрес доставки');
            }

            $shipmentData = array_merge($shipmentData, [
                'tariff_code' => $tariff['code'] ?? null,
                'price' => $tariff['total_sum'] ?? 0,
                'period_min' => $tariff['period_min'] ?? null,
                'period_max' => $tariff['period_max'] ?? null,
            ]);
        }

        // if (!empty($deliveryData['weight'])) {
        //     $shipmentData['weight'] = $deliveryData['weight'];
        // }

        // if (!empty($deliveryData['dimensions'])) {
        //     $shipmentData['dimensions'] = json_encode($deliveryData['dimensions']);
        // }

        Shipment::create($shipmentData);
    }

    private function sendNotifications($client, $order)
    {
        $profile = UserProfile::where('client_id', $client->id)->first();

        if ($profile && $profile->phone) {
            $telegramService = new TelegramNotificationService();
            $telegramService->sendOrderNotificationToClient($order, $profile);
        }
    }


    public function pay(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,online',
            'payment_provider' => 'required_if:payment_method,online|nullable|string',
            'payment_id' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'payment_data' => 'nullable|array',
        ]);

        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Заказ уже оплачен'], 400);
        }

        if (floatval($validated['amount']) != floatval($order->total_amount)) {
            return response()->json(['error' => 'Сумма оплаты не совпадает с суммой заказа'], 400);
        }

        DB::beginTransaction();

        try {
            $payment = $order->payment()->create([
                'payment_method' => $validated['payment_method'],
                'payment_provider' => $validated['payment_provider'],
                'payment_id' => $validated['payment_id'] ?? null,
                'amount' => $validated['amount'],
                'status' => 'success',
                'payment_data' => json_encode($validated['payment_data'] ?? []),
                'processed_at' => now(),
            ]);

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $client_profile = UserProfile::where('user_id', $order->client_id)->first();

            if ($client_profile && $client_profile->phone) {
                // $whatsapp_service = new WhatsappService();
                // $whatsapp_service->payment_notification(
                //     $client_profile->phone,
                //     $payment->id,
                //     $payment->processed_at,
                //     $validated['amount']
                // );
                $telegram_notification = new TelegramNotificationService();
                $telegram_notification->sendPaymentNotificationToClient($payment, $client_profile);
            }

            DB::commit();

            return response()->json(['message' => 'Оплата успешно проведена']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка оплаты: ' . $e->getMessage()], 500);
        }
    }


    public function show(Order $order)
    {
        try {
            $order->load([
                'client.profile',
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
                    'full_name' => $order->client->profile->full_name,
                    'email' => $order->client->email,
                    'phone' => $order->client->phone,
                    'address' => $order->client->address // добавлено
                ] : null,

                // Товары в заказе
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image' => $item->product->getFirstMediaUrl('images'),
                            'article' => $item->product->article // добавлено
                        ] : null,
                        'variant' => $item->productVariant,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'reserve' => $item->reserve_quantity // добавлено
                    ];
                }),

                // История изменений
                'history' => $order->history->map(function ($record) {
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

    public function update(Request $request, Order $order)
    {
        try {
            $validated = $request->validate([
                'status' => [
                    'required',
                    'string',
                    Rule::in(Order::getStatusValues()),
                ],

                'payment_status' => [
                    'required',
                    'string',
                    Rule::in(Order::getPaymentStatusValues()),
                ],

                'notes' => 'nullable|string',
                'delivery_date' => 'nullable|date_format:Y-m-d\TH:i:s\Z', // Валидация для даты доставки
                'delivery_method_id' => 'nullable|exists:delivery_methods,id',
            ]);

            $order->update([
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
                'notes' => $validated['notes'],
//                'delivery_date' => $validated['delivery_date'], // Добавляем дату доставки,
//                'delivery_method_id' => $validated['delivery_method_id'] ?? null, // Обновляем delivery_method_id
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
                'success' => true,
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


    public function updateStatus(Order $order, Request $request)
    {
        $order->update(['status' => $request->get('changing_status')]);

        return response()->json([
            'success' => true,
            'message' => 'Заказ обновлен!'
        ]);
    }

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
