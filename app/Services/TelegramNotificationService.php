<?php

namespace App\Services;

use App\Models\OrderPayment;
use App\Models\UserProfile;
use DefStudio\Telegraph\Facades\Telegraph;
use App\Models\Order;
use App\Models\Client;
use App\Models\Manager;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public function sendOrderNotificationToClient(Order $order, UserProfile $profile): void
    {
        if (!$profile->telegram_user_id) {
            Log::error("Client {$profile->user_id} does not have an associated TelegraphChat.");
            return;
        }

        $message = $this->build_client_message_2($order);
        try {
            Telegraph::chat($profile->telegram_user_id)->message($message)->send();
        } catch (\Exception $e) {
            Log::error("Failed to send notification to client {$profile->user_id}: " . $e->getMessage());
        }
    }

    public function sendPaymentNotificationToClient(OrderPayment $payment, UserProfile $profile): void
    {
        if (!$profile->telegram_user_id) {
            Log::error("Client {$profile->user_id} does not have an associated TelegraphChat.");
            return;
        }

        $message = $this->build_payment_client_message($payment);
        try {
            Telegraph::chat($profile->telegram_user_id)->message($message)->send();
        } catch (\Exception $e) {
            Log::error("Failed to send notification to client {$profile->user_id}: " . $e->getMessage());
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

    private function build_client_message_2(Order $order)
    {
        $message = "*Спасибо за ваш заказ!*🎉\n";
        $message .= "Вы оформили заказ №{$order->id} от {$order->created_at->format('d.m.Y в H:i')} на сумму {$order->total_amount}.\n\n";

        $message .= "Состав заказа:\n";
        foreach ($order->items as $item) {
            if ($item->productVariant) {
                $message .= "- {$item->productVariant->name} x {$item->quantity}\n";
            } else {
                $message .= "- {$item->product->name} x {$item->quantity}\n";
            }
        }

        $message .= "\n";

        $message .= "Мы уже начали обработку. Ожидайте, пожалуйста, подтверждение.\n";
        $message .= "С уважением, команда *Again*!\n\n";

        return $message;
    }

    private function build_payment_client_message(OrderPayment $payment)
    {
        $payment_message = "*Спасибо за ваш платёж!*🎉\n";
        $payment_message .= "Мы успешно получили ваш платёж №{$payment->id} от {$payment->created_at->format('d.m.Y в H:i')} на сумму {$payment->amount}.\n";
        $payment_message .= "Если у вас есть вопросы, пожалуйста, свяжитесь с нашей поддержкой.\n";
        $payment_message .= "С уважением, команда *Again*!\n\n";
        return $payment_message;
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
