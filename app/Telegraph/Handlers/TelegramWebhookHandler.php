<?php

namespace App\Telegraph\Handlers;

use App\Models\Conversation;
use App\Services\Messaging\ConversationService;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
class TelegramWebhookHandler extends WebhookHandler
{
    protected function handleChatMessage(Stringable $text): void
    {
        try {
            // Получаем или создаем диалог
            $conversation = Conversation::firstOrCreate(
                [
                    'source' => 'telegram',
                    'external_id' => $this->chat->chat_id
                ],
                [
                    'status' => 'new',
                    'last_message_at' => now()
                ]
            );

            // Создаем сообщение через сервис
            app(ConversationService::class)->addMessage($conversation, [
                'direction' => Message::DIRECTION_INCOMING,
                'content' => $text->toString(),
                'content_type' => Message::CONTENT_TYPE_TEXT,
                'status' => Message::STATUS_SENT,
                'source_data' => $this->message->toArray()
            ]);

            // Отправляем подтверждение
            $this->chat->html('✅ Ваше сообщение получено. Менеджер ответит вам в ближайшее время.')->send();

        } catch (\Exception $e) {
            Log::error('Error handling chat message: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => [
                    'chat_id' => $this->chat->chat_id,
                    'text' => $text->toString()
                ]
            ]);
            $this->chat->html('❌ Произошла ошибка при обработке сообщения. Пожалуйста, попробуйте позже.')->send();
        }
    }

    protected function handleCommand(Stringable $command): void
    {
        if ($command->toString() === 'start') {
            $message = "👋 Здравствуйте!\n\n";
            $message .= "Добро пожаловать в чат поддержки. Здесь вы можете задать любой вопрос, и наши менеджеры помогут вам.\n\n";
            $message .= "✍️ Просто напишите ваш вопрос в этот чат, и мы ответим в ближайшее время.\n\n";
            $message .= "💡 Вы можете отправлять:\n";
            $message .= "- Текстовые сообщения\n";
            $message .= "- Фотографии\n";
            $message .= "- Документы\n\n";
            $message .= "🕐 Время работы менеджеров: ПН-ПТ с 9:00 до 18:00";

            $this->chat->html($message)->send();
        }
    }

    protected function handleDocument(): void
    {
        try {
            $document = $this->message->document;
            
            $conversation = Conversation::firstOrCreate(
                [
                    'source' => 'telegram',
                    'external_id' => $this->chat->chat_id
                ],
                [
                    'status' => 'new',
                    'last_message_at' => now()
                ]
            );

            app(ConversationService::class)->addMessage($conversation, [
                'direction' => Message::DIRECTION_INCOMING,
                'content' => $this->message->caption ?? 'Документ',
                'content_type' => Message::CONTENT_TYPE_FILE,
                'status' => Message::STATUS_SENT,
                'source_data' => $this->message->toArray(),
                'attachments' => [[
                    'type' => 'document',
                    'file_id' => $document->file_id,
                    'file_name' => $document->file_name,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size
                ]]
            ]);

            $this->chat->html('✅ Документ получен')->send();

        } catch (\Exception $e) {
            Log::error('Error handling document:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->chat->html('❌ Ошибка обработки документа')->send();
        }
    }

    protected function handlePhoto(): void
    {
        try {
            $photo = $this->message->photo;
            
            $conversation = Conversation::firstOrCreate(
                [
                    'source' => 'telegram',
                    'external_id' => $this->chat->chat_id
                ],
                [
                    'status' => 'new',
                    'last_message_at' => now()
                ]
            );

            app(ConversationService::class)->addMessage($conversation, [
                'direction' => Message::DIRECTION_INCOMING,
                'content' => $this->message->caption ?? 'Фото',
                'content_type' => Message::CONTENT_TYPE_IMAGE,
                'status' => Message::STATUS_SENT,
                'source_data' => $this->message->toArray(),
                'attachments' => [[
                    'type' => 'photo',
                    'file_id' => $photo->file_id,
                    'file_size' => $photo->file_size
                ]]
            ]);

            $this->chat->html('✅ Фото получено')->send();

        } catch (\Exception $e) {
            Log::error('Error handling photo:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->chat->html('❌ Ошибка обработки фото')->send();
        }
    }
} 