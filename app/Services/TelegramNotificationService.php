<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use App\Models\Order;
use App\Models\Client;
use App\Models\Manager;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public function sendOrderNotificationToClient(Order $order, Client $client): void
    {
        if (!$client->chat) {
            Log::error("Client {$client->id} does not have an associated TelegraphChat.");
            return;
        }

        $message = $this->buildClientMessage($order);
        try {
            Telegraph::chat($client->chat->chat_id)->markdown($message)->send();
        } catch (\Exception $e) {
            Log::error("Failed to send notification to client {$client->id}: " . $e->getMessage());
        }
    }

    public function sendOrderNotificationToManager(Order $order, Manager $manager): void
    {
        $message = $this->buildManagerMessage($order);
        try {
            Telegraph::chat($manager->telegraph_chat_id)->markdown($message)->send();
        } catch (\Exception $e) {
            Log::error("Failed to send notification to manager {$manager->id}: " . $e->getMessage());
        }
    }

    private function buildClientMessage(Order $order): string
    {
        $message = "Ваш заказ успешно создан!\n\n";
        $message .= "Номер заказа: {$order->order_number}\n";
        $message .= "Сумма: {$order->total_amount} руб.\n\n";

        $message .= "Состав заказа:\n";
        foreach ($order->items as $item) {
            $message .= "- {$item->product->name} x {$item->quantity}\n";
        }


        return $message;
    }

    private function buildManagerMessage(Order $order): string
    {
        $message = "Новый заказ создан!\n\n";
        $message .= "Номер заказа: {$order->order_number}\n";
        $message .= "Сумма: {$order->total_amount} руб.\n\n";

        $message .= "Состав заказа:\n";
        foreach ($order->items as $item) {
            $message .= "- {$item->product->name} x {$item->quantity}\n";
        }

        $message .= "\nКлиент: {$order->client->first_name} {$order->client->last_name}";
        $message .= "\nTG: `@{$order->client->username}`";
        $message .= "\nТелефон: {$order->client->phone}";

        return $message;
    }
}
