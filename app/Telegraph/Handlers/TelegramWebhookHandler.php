<?php

namespace App\Telegraph\Handlers;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Messaging\ConversationService;
use App\Services\Telegram\TelegramService;
use App\Traits\ClientControllerTrait;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Log;
use App\Models\Message;

class TelegramWebhookHandler extends WebhookHandler
{

    use ClientControllerTrait;


    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }


    public function start()
    {

        $chat = $this->getChat();

        $chat->chatAction(ChatActions::TYPING)->send();

        $client_profile = $this->user_profile(true);

        if (!$client_profile)
            return;

        $user_name = '';
        if ($client_profile->first_name) {
            $user_name .= $client_profile->first_name . " ";
        }
        if ($client_profile->last_name) {
            $user_name .= $client_profile->last_name;
        }
        if (empty($user_name)) {
            $client = Client::where('id', $client_profile->client_id)->whereNull('deleted_at')->first();
            $user_name = $client->email;
        }

        $chat->message("Привет, {$user_name}! Мы успешно нашли ваш аккаунт. Напишите команду */orders*, чтобы посмотреть свои Заказы.")->send();
    }


    private function user_profile($await_email = false): UserProfile|null
    {
        $telegramId = $this->getUserId();
        $chat = $this->getChat();

        $client_profile = UserProfile::where('telegram_user_id', $telegramId)->first();

        if (!$client_profile) {
            // save state and wait email
            if ($await_email) {
                $chat->message("Привет! Пожалуйста, отправьте свой email, чтобы мы могли найти ваш аккаунт.")->send();
                cache()->put("awaiting_email_$telegramId", true, now()->addMinutes(10));
            }
            return null;
        }
        return $client_profile;
    }

    public function handleUnknownCommand(Stringable $text): void
    {
        $this->reply("Извините, я не распознал эту команду. Пожалуйста, используйте одну из доступных команд или напишите /help для получения списка команд.");
    }

    public function handleChatMessage(Stringable $text): void
    {

        $telegramId = $this->getUserId();
        $chatId = $this->message->chat()->id();
        $firstName = $this->message->from()->firstName();
        $lastName = $this->message->from()->lastName();
        $content = (string)$text;
        $client_profile = UserProfile::where('telegram_user_id', $telegramId)->first();

        $awaitingEmail = cache("awaiting_email_$telegramId");

        Log::info('TelegramWebhookHandler: User info', [
            'telegram_id' => $telegramId,
            'chat_id' => $chatId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'content' => $content,
            'client_profile' => $client_profile,
            '$awaitingEmail' => $awaitingEmail
        ]);


        if ($awaitingEmail) {
            $email = $this->message->text();

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->reply("Пожалуйста, введите корректный email.");
                return;
            }
            // Поиск пользователя по email
            $client = Client::where('email', $email)->whereNull('deleted_at')->first();

            if ($client) {
                $client_profile = $this->check_client_with_same_email($client);

                if (!$client_profile) {
                    $client_profile = UserProfile::create([
                        'client_id' => $client->id,
                    ]);
                } else {
                    $client_profile->update([
                        'client_id' => $client->id,
                    ]);
                }

                // Сохраняем telegram_user_id в профиль
                $client_profile->update([
                    'telegram_user_id' => $telegramId,
                ]);

                cache()->forget("awaiting_email_$telegramId");

                $this->reply("Спасибо! Ваш аккаунт успешно привязан к Telegram. Напишите команду */orders*, чтобы посмотреть свои Заказы.");
            } else {
                cache()->forget("awaiting_email_$telegramId");
                $this->reply("Пользователь с таким email не найден. Пожалуйста, сначала зарегистрируйтесь на нашем сайте.");
            }

            return;
        }

        $this->telegramService->findOrCreateConversationAndSendMessage(
            $telegramId,
            $client_profile,
            $content,
        );

    }

    public function orders()
    {
        $chat = $this->getChat();

        $chat->chatAction(ChatActions::TYPING)->send();

        $client_profile = $this->user_profile();

        if (!$client_profile) {
            $this->start();
            return;
        }

        $client = Client::where('id', $client_profile->client_id)->whereNull('deleted_at')->first();

        if (!$client) {
            $this->start();
            return;
        }

        $this->send_order_data($client, $chat);
    }

    public function send_order_data(
        Client                         $client,
        \DefStudio\Telegraph\Telegraph $chat
    )
    {
        $find_pending_orders_ids = Order
            ::whereIn('status', [Order::STATUS_PROCESSING, Order::STATUS_NEW])
            ->whereNull("deleted_at")
            // once you found by clients, it's enought
            // because second time you request with ids
            ->where('client_id', $client->id)
            ->pluck('id')->toArray();

        if (count($find_pending_orders_ids) <= 0) {
            $this->reply("На данный момент нет ожидающих заказов.");
            return;
        }

        $find_pending_orders = Order
            ::whereIn('id', $find_pending_orders_ids)
            ->with(['payments', 'items'])
            ->get();

        foreach ($find_pending_orders as $order) {
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

            $chat->message($message)->send();

            foreach ($order->payments as $payment) {
                $payment_message = "*Спасибо за ваш платёж!*🎉\n";
                $payment_message .= "Мы успешно получили ваш платёж №{$payment->id} от {$payment->created_at->format('d.m.Y в H:i')} на сумму {$payment->amount}.\n";
                $payment_message .= "Если у вас есть вопросы, пожалуйста, свяжитесь с нашей поддержкой.\n";
                $payment_message .= "С уважением, команда *Again*!\n\n";
                $chat->message($payment_message)->send();
            }
        }
    }

    public function help()
    {
        $chat = Telegraph::chat($this->message->from()->id());

        $chat->message("Привет! Вот что я умею делать:")
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make("Начать работу с ботом")->action('start'),
                    Button::make("Посмотреть мои заказы")->action('orders'),
                    Button::make("Перейти на сайт")->url(env("FRONTEND_URL")),
                ])
            )
            ->send();
    }


    private function reset()
    {
        $telegramId = $this->getUserId();
        cache()->forget("awaiting_email_$telegramId");
    }

    public function cancel()
    {
        $chat = $this->getChat();

        $chat->chatAction(ChatActions::TYPING)->send();

        $client_profile = $this->user_profile();

        if ($client_profile) {
            $client_profile->update([
                'telegram_user_id' => null,
            ]);
        }

        $this->reset();
        $chat->message("Авторизация отменена. Вы можете писать свои вопросы или использовать /start для повторной авторизации.")->send();
    }

    protected function getChat(): \DefStudio\Telegraph\Telegraph
    {
        if ($this->message?->chat()?->id()) {
            return Telegraph::chat($this->message->chat()->id());
        }

        if ($this->callbackQuery?->message()?->chat()?->id()) {
            return Telegraph::chat($this->callbackQuery->message()?->chat()->id());
        }

        throw new \RuntimeException("Не удалось определить chat ID для ответа.");
    }

    protected function getUserId(): int
    {
        if ($this->message?->from()?->id()) {
            return $this->message->from()->id();
        }

        if ($this->callbackQuery?->from()?->id()) {
            return $this->callbackQuery->from()->id();
        }

        throw new \RuntimeException("Не удалось определить user ID.");
    }

}
