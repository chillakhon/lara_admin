<?php

namespace App\Telegraph\Handlers;

use App\Models\Conversation;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Messaging\ConversationService;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
class TelegramWebhookHandler extends WebhookHandler
{

    public function start()
    {
        Telegraph::chatAction(ChatActions::TYPING)->send();

        $telegramId = $this->message->from()->id();

        $user = UserProfile::where('telegram_user_id', $telegramId)->first();

        if (!$user) {
            $this->reply("Привет! Пожалуйста, отправь свой email, чтобы мы могли найти твой аккаунт.");
            // save state and wait email
            cache()->put("awaiting_email_$telegramId", true, now()->addMinutes(10));
            return;
        }

        $this->reply("Ты уже зарегистрирован.");
    }

    public function hello()
    {
        $this->reply("Hello world, how is it going? Hama saday?");
    }


    public function handleUnknownCommand(Stringable $text): void
    {
        if ($text->value() === '/start') {
            $this->reply("I'm very glad to see you. Let's start out job");
        } else {
            $this->reply("Unknown command bro");
        }
    }

    public function handleChatMessage(Stringable $text): void
    {
        $telegramId = $this->message->from()->id();
        $awaitingEmail = cache("awaiting_email_$telegramId");

        if ($awaitingEmail) {
            $email = $this->message->text();

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->reply("Пожалуйста, введи корректный email.");
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

                $this->reply("Спасибо! Твой аккаунт привязан к Telegram.");
            } else {
                $this->reply("Мы не нашли пользователя с таким email. Попробуй ещё раз.");
            }

            return;
        }

        // если не ожидается email
        $this->reply("Я тебя не понял. Напиши */start* чтобы начать.");

        Log::info(json_encode($this->message->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function help()
    {
        $this->reply("*Hello*! I can only reply for now");
    }

    public function actions()
    {
        Telegraph::message("Выбери какое-то действие")
            ->keyboard(Keyboard::make()->buttons([
                Button::make("Find my account with my email")->action('find_email'),
                Button::make("Url of this dev")->url("https://youtube.com/@flutterguides?si=VddZYChbwFHGP0AB"),
            ]))->send();
    }


    public function find_email()
    {
        Telegraph::message("Yahay bl")->send();
    }

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