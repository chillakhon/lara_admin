<?php

namespace App\Telegraph\Handlers;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Messaging\ConversationService;
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

    public function start()
    {
        $chat = $this->getChat();

        $chat->chatAction(ChatActions::TYPING)->send();

        $user_profile = $this->user_profile(true);

        if (!$user_profile)
            return;

        $user_name = '';
        if ($user_profile->first_name) {
            $user_name .= $user_profile->first_name . " ";
        }
        if ($user_profile->last_name) {
            $user_name .= $user_profile->last_name;
        }
        if (empty($user_name)) {
            $user = User::where('id', $user_profile->user_id)->first();
            $user_name = $user->email;
        }

        $chat->message("Привет, {$user_name}! Мы успешно нашли ваш аккаунт. Напишите команду */orders*, чтобы посмотреть свои ожидающие заказы.")->send();
    }


    private function user_profile($await_email = false): UserProfile|null
    {
        $telegramId = $this->getUserId();
        $chat = $this->getChat();

        $user_profile = UserProfile::where('telegram_user_id', $telegramId)->first();

        if (!$user_profile) {
            // save state and wait email
            if ($await_email) {
                $chat->message("Привет! Пожалуйста, отправьте свой email, чтобы мы могли найти ваш аккаунт.")->send();
                cache()->put("awaiting_email_$telegramId", true, now()->addMinutes(10));
            }
            return null;
        }
        return $user_profile;
    }

    public function handleUnknownCommand(Stringable $text): void
    {
        $this->reply("Извините, я не распознал эту команду. Пожалуйста, используйте одну из доступных команд или напишите /help для получения списка команд.");
    }

    public function handleChatMessage(Stringable $text): void
    {
        $telegramId = $this->message->from()->id();
        $awaitingEmail = cache("awaiting_email_$telegramId");

        if ($awaitingEmail) {
            $email = $this->message->text();

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->reply("Пожалуйста, введите корректный email.");
                return;
            }
            // Поиск пользователя по email
            $user = User::where('email', $email)->first();

            if ($user) {
                $user_profile = UserProfile::where('user_id', $user->id)->first();

                if (!$user_profile) {
                    $user_profile = UserProfile::create([
                        'user_id' => $user->id,
                    ]);
                }
                // Сохраняем telegram_user_id в профиль
                $user_profile->update([
                    'telegram_user_id' => $telegramId,
                ]);

                cache()->forget("awaiting_email_$telegramId");

                $this->reply("Спасибо! Ваш аккаунт успешно привязан к Telegram. Напишите команду */orders*, чтобы посмотреть свои ожидающие заказы.");
            } else {
                cache()->forget("awaiting_email_$telegramId");
                $this->reply("Пользователь с таким email не найден. Пожалуйста, сначала зарегистрируйтесь на нашем сайте.");
            }

            return;
        }

        // если не ожидается email
        $this->reply("Я вас не понял. Напишите */start* чтобы начать.");

        Log::info(json_encode($this->message->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function orders()
    {
        $chat = $this->getChat();

        $chat->chatAction(ChatActions::TYPING)->send();

        $user_profile = $this->user_profile();

        if (!$user_profile)
            return;

        $this->send_order_data($user_profile, $chat);
    }

    public function send_order_data(
        UserProfile $user_profile,
        \DefStudio\Telegraph\Telegraph $chat
    ) {
        $find_pending_orders_ids = Order
            ::whereIn('status', [Order::STATUS_PROCESSING, Order::STATUS_NEW])
            ->whereNull("deleted_at")
            ->pluck('id')->toArray();

        if (count($find_pending_orders_ids) <= 0) {
            $this->reply("На данный момент нет ожидающих заказов.");
            return;
        }

        $find_pending_orders = Order
            ::whereIn('id', $find_pending_orders_ids)
            ->where('client_id', $user_profile->user_id)
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
                    Button::make("Перейти на сайт")->url(env("APP_URL")),
                ])
            )
            ->send();
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

    // public function actions()
    // {
    //     Telegraph::message("Выбери какое-то действие")
    //         ->keyboard(Keyboard::make()->buttons([
    //             Button::make("Find my account with my email")->action('find_email'),
    //             Button::make("Url of this dev")->url("https://youtube.com/@flutterguides?si=VddZYChbwFHGP0AB"),
    //         ]))->send();
    // }

    // protected function handleChatMessage(Stringable $text): void
    // {
    //     try {
    //         // Получаем или создаем диалог
    //         $conversation = Conversation::firstOrCreate(
    //             [
    //                 'source' => 'telegram',
    //                 'external_id' => $this->chat->chat_id
    //             ],
    //             [
    //                 'status' => 'new',
    //                 'last_message_at' => now()
    //             ]
    //         );

    //         // Создаем сообщение через сервис
    //         app(ConversationService::class)->addMessage($conversation, [
    //             'direction' => Message::DIRECTION_INCOMING,
    //             'content' => $text->toString(),
    //             'content_type' => Message::CONTENT_TYPE_TEXT,
    //             'status' => Message::STATUS_SENT,
    //             'source_data' => $this->message->toArray()
    //         ]);

    //         // Отправляем подтверждение
    //         $this->chat->html('✅ Ваше сообщение получено. Менеджер ответит вам в ближайшее время.')->send();

    //     } catch (\Exception $e) {
    //         Log::error('Error handling chat message: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //             'data' => [
    //                 'chat_id' => $this->chat->chat_id,
    //                 'text' => $text->toString()
    //             ]
    //         ]);
    //         $this->chat->html('❌ Произошла ошибка при обработке сообщения. Пожалуйста, попробуйте позже.')->send();
    //     }
    // }

    // protected function handleCommand(Stringable $command): void
    // {
    //     if ($command->toString() === 'start') {
    //         $message = "👋 Здравствуйте!\n\n";
    //         $message .= "Добро пожаловать в чат поддержки. Здесь вы можете задать любой вопрос, и наши менеджеры помогут вам.\n\n";
    //         $message .= "✍️ Просто напишите ваш вопрос в этот чат, и мы ответим в ближайшее время.\n\n";
    //         $message .= "💡 Вы можете отправлять:\n";
    //         $message .= "- Текстовые сообщения\n";
    //         $message .= "- Фотографии\n";
    //         $message .= "- Документы\n\n";
    //         $message .= "🕐 Время работы менеджеров: ПН-ПТ с 9:00 до 18:00";

    //         $this->chat->html($message)->send();
    //     }
    // }

    // protected function handleDocument(): void
    // {
    //     try {
    //         $document = $this->message->document;

    //         $conversation = Conversation::firstOrCreate(
    //             [
    //                 'source' => 'telegram',
    //                 'external_id' => $this->chat->chat_id
    //             ],
    //             [
    //                 'status' => 'new',
    //                 'last_message_at' => now()
    //             ]
    //         );

    //         app(ConversationService::class)->addMessage($conversation, [
    //             'direction' => Message::DIRECTION_INCOMING,
    //             'content' => $this->message->caption ?? 'Документ',
    //             'content_type' => Message::CONTENT_TYPE_FILE,
    //             'status' => Message::STATUS_SENT,
    //             'source_data' => $this->message->toArray(),
    //             'attachments' => [
    //                 [
    //                     'type' => 'document',
    //                     'file_id' => $document->file_id,
    //                     'file_name' => $document->file_name,
    //                     'mime_type' => $document->mime_type,
    //                     'file_size' => $document->file_size
    //                 ]
    //             ]
    //         ]);

    //         $this->chat->html('✅ Документ получен')->send();

    //     } catch (\Exception $e) {
    //         Log::error('Error handling document:', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         $this->chat->html('❌ Ошибка обработки документа')->send();
    //     }
    // }

    // protected function handlePhoto(): void
    // {
    //     try {
    //         $photo = $this->message->photo;

    //         $conversation = Conversation::firstOrCreate(
    //             [
    //                 'source' => 'telegram',
    //                 'external_id' => $this->chat->chat_id
    //             ],
    //             [
    //                 'status' => 'new',
    //                 'last_message_at' => now()
    //             ]
    //         );

    //         app(ConversationService::class)->addMessage($conversation, [
    //             'direction' => Message::DIRECTION_INCOMING,
    //             'content' => $this->message->caption ?? 'Фото',
    //             'content_type' => Message::CONTENT_TYPE_IMAGE,
    //             'status' => Message::STATUS_SENT,
    //             'source_data' => $this->message->toArray(),
    //             'attachments' => [
    //                 [
    //                     'type' => 'photo',
    //                     'file_id' => $photo->file_id,
    //                     'file_size' => $photo->file_size
    //                 ]
    //             ]
    //         ]);

    //         $this->chat->html('✅ Фото получено')->send();

    //     } catch (\Exception $e) {
    //         Log::error('Error handling photo:', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         $this->chat->html('❌ Ошибка обработки фото')->send();
    //     }
    // }
}