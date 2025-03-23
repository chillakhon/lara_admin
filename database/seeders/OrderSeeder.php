<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Client;
use App\Models\PromoCode;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Отключаем логирование запросов
        DB::disableQueryLog();

        // Начинаем транзакцию
        DB::transaction(function () {
            // Создаем клиента (если нужно)
            $client = Client::factory()->create();

            // Создаем промокод (если нужно)
            $promoCode = PromoCode::factory()->create();

            // Создаем продукт и его вариант
            $product = Product::factory()->create();
            $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

            // Создаем заказ
            $order = Order::create([
                'order_number' => 'ORD' . time(), // Уникальный номер заказа
                'client_id' => $client->id, // ID клиента
                'lead_id' => null, // Пока не используем
                'status' => 'new', // Статус заказа
                'payment_status' => 'pending', // Статус оплаты
                'total_amount' => 0, // Пока 0, обновим позже
                'discount_amount' => 0, // Скидка
                'promo_code_id' => 1, // ID промокода
                'payment_method' => null, // Метод оплаты
                'payment_provider' => null, // Платежный провайдер
                'payment_id' => null, // ID транзакции
                'paid_at' => null, // Дата оплаты
                'source' => null, // Источник заказа
                'utm_source' => null, // UTM-метки
                'utm_medium' => null,
                'utm_campaign' => null,
                'utm_content' => null,
                'utm_term' => null,
                'ip_address' => null, // IP-адрес
                'user_agent' => null, // User Agent
                'notes' => null, // Заметки
            ]);

            // Создаем товары в заказе
            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2, // Количество товара
                'price' => $product->price, // Цена товара
                'discount' => 0, // Скидка на товар
            ]);

            // Обновляем общую сумму заказа
            $order->update([
                'total_amount' => $order->items->sum(function ($item) {
                    return $item->quantity * $item->price;
                }),
            ]);

            echo "Заказ успешно создан! ID заказа: " . $order->id . "\n";
        });
    }
}
