<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadType;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\DeliveryMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            
            // Делаем поля для идентификации опциональными
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            
            // Добавляем поля для создания лида
            'customer_name' => 'required_without:client_id,lead_id|string|max:255',
            'customer_phone' => 'required_without:client_id,lead_id|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            
            'payment_method' => 'required|in:cash,card,online',
            'payment_provider' => 'required_if:payment_method,online|nullable|string',
            'promo_code_id' => 'nullable|exists:promo_codes,id',
            'delivery_method_id' => 'required|exists:delivery_methods,id',
            'delivery_address' => 'required|array',
            'delivery_address.city' => 'required|string',
            'delivery_address.street' => 'required|string',
            'delivery_address.house' => 'required|string',
            'delivery_address.apartment' => 'nullable|string',
            'delivery_address.postal_code' => 'required|string',
            'delivery_comment' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Определяем источник заказа (клиент, лид или новый лид)
            $client = null;
            $lead = null;

            if ($request->client_id) {
                $client = Client::findOrFail($request->client_id);
            } elseif ($request->lead_id) {
                $lead = Lead::findOrFail($request->lead_id);
            } else {
                // Создаем нового лида
                $leadType = LeadType::where('key', 'order')->first();
                
                $lead = Lead::create([
                    'type_id' => $leadType->id,
                    'status' => 'new',
                    'data' => [
                        'name' => $request->customer_name,
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                    ],
                    'source' => $request->header('Referer'),
                    'utm_source' => $request->utm_source,
                    'utm_medium' => $request->utm_medium,
                    'utm_campaign' => $request->utm_campaign,
                    'utm_content' => $request->utm_content,
                    'utm_term' => $request->utm_term,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }

            // Рассчитываем общую сумму заказа
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variant = null;
                
                if (isset($item['variant_id'])) {
                    $variant = $product->variants()->findOrFail($item['variant_id']);
                    $price = $variant->price;
                } else {
                    $price = $product->price;
                }

                $subtotal = $price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];
            }

            // Применяем промокод если есть
            $promoDiscount = 0;
            if ($request->promo_code_id) {
                $promoCode = PromoCode::findOrFail($request->promo_code_id);
                if ($promoCode->isValid()) {
                    $promoDiscount = $promoCode->calculateDiscount($totalAmount);
                    $totalAmount -= $promoDiscount;
                }
            }

            // Создаем заказ
            $order = Order::create([
                'order_number' => 'ORD-' . Str::random(10),
                'client_id' => $client?->id,
                'lead_id' => $lead?->id,
                'status' => Order::STATUS_NEW,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'total_amount' => $totalAmount,
                'discount_amount' => $promoDiscount,
                'promo_code_id' => $request->promo_code_id,
                'payment_method' => $request->payment_method,
                'payment_provider' => $request->payment_provider,
                'source' => $request->header('Referer'),
                'utm_source' => $request->utm_source,
                'utm_medium' => $request->utm_medium,
                'utm_campaign' => $request->utm_campaign,
                'utm_content' => $request->utm_content,
                'utm_term' => $request->utm_term,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Создаем позиции заказа
            $order->items()->createMany($orderItems);

            // Записываем историю
            $order->history()->create([
                'status' => Order::STATUS_NEW,
                'comment' => $client 
                    ? 'Заказ создан от существующего клиента' 
                    : 'Заказ создан от лида'
            ]);

            // Добавляем информацию о доставке
            $deliveryMethod = DeliveryMethod::findOrFail($request->delivery_method_id);
            $deliveryService = $deliveryMethod->getDeliveryService();
            
            // Рассчитываем стоимость доставки
            $deliveryRate = $deliveryService->calculateRate($order)->first();
            
            $order->update([
                'delivery_method_id' => $request->delivery_method_id,
                'delivery_address' => $request->delivery_address,
                'delivery_cost' => $deliveryRate['price'],
                'delivery_comment' => $request->delivery_comment,
                'total_amount' => $order->total_amount + $deliveryRate['price']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order->load('items', 'client', 'lead', 'promoCode', 'deliveryMethod'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
