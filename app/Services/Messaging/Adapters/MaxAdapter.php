<?php

namespace App\Services\Messaging\Adapters;

use App\Models\MaxSettings;
use App\Services\Messaging\AbstractMessageAdapter;
use BushlanovDev\MaxMessengerBot\Api;
use Illuminate\Support\Facades\Log;

class MaxAdapter extends AbstractMessageAdapter
{
    protected ?MaxSettings $settings = null;

    protected ?Api $apiClient = null;

    public function __construct()
    {
        // Ленивая инициализация - не загружаем настройки в конструкторе
        // Это позволяет избежать ошибок при недоступности БД
    }

    /**
     * Получить API клиент (ленивая инициализация)
     */
    protected function getApiClient(): Api
    {
        if ($this->apiClient === null) {
            $this->settings = MaxSettings::where('is_active', true)->first();

            if (! $this->settings) {
                Log::error('MaxAdapter: MaxSettings not found in database');
                throw new \RuntimeException('Max settings не найдены в БД. Сначала настрой Max интеграцию в админке.');
            }

            if (! $this->settings->bot_token) {
                Log::error('MaxAdapter: bot_token is empty');
                throw new \RuntimeException('Bot token не установлен');
            }

            $this->apiClient = new Api($this->settings->bot_token);
        }

        return $this->apiClient;
    }

    /**
     * Отправить сообщение в Max
     *
     * @param  string  $externalId  - ID пользователя Max (user_id)
     * @param  string|null  $content  - Текст сообщения
     * @param  array  $attachments  - Вложения
     */
    public function sendMessage(string $externalId, ?string $content, array $attachments = []): bool
    {
        try {
            Log::info('MaxAdapter: Sending message', [
                'user_id' => $externalId,
                'has_content' => ! empty($content),
                'attachments_count' => count($attachments),
            ]);

            // Подготавливаем вложения для Max API
            $maxAttachments = [];
            if (! empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $maxAttachment = $this->prepareAttachment($attachment);
                    if ($maxAttachment) {
                        $maxAttachments[] = $maxAttachment;
                    }
                }
            }

            // Отправляем сообщение с текстом и вложениями
            $message = $this->getApiClient()->sendUserMessage(
                userId: (int) $externalId,
                text: $content,
                attachments: ! empty($maxAttachments) ? $maxAttachments : null
            );

            if (! $message || ! $message->body) {
                Log::error('MaxAdapter: Failed to send message', [
                    'user_id' => $externalId,
                ]);

                return false;
            }

            Log::info('MaxAdapter: Message sent successfully', [
                'user_id' => $externalId,
                'message_id' => $message->body->mid ?? null,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Exception while sending message', [
                'user_id' => $externalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Подготовить вложение для отправки через Max API
     *
     * @param  array  $attachment  - Вложение с полями: type, url, file_path
     * @return \BushlanovDev\MaxMessengerBot\Models\Attachments\Requests\AbstractAttachmentRequest|null
     */
    protected function prepareAttachment(array $attachment)
    {
        try {
            $type = $attachment['type'] ?? 'file';
            $url = $attachment['url'] ?? null;
            $filePath = $attachment['file_path'] ?? null;

            Log::info('MaxAdapter: Preparing attachment', [
                'type' => $type,
                'has_url' => ! empty($url),
                'has_file_path' => ! empty($filePath),
                'attachment' => $attachment,
            ]);

            // Приоритет: сначала пытаемся использовать file_path (локальный файл)
            if (! empty($filePath)) {
                // Получаем полный путь к файлу в storage/app/public
                $fullPath = storage_path('app/public/'.$filePath);

                if (file_exists($fullPath)) {
                    Log::info('MaxAdapter: Using local file path', ['full_path' => $fullPath]);

                    return $this->uploadAttachment($type, $fullPath);
                } else {
                    Log::warning('MaxAdapter: File not found at path', ['full_path' => $fullPath]);
                }
            }

            // Если file_path не сработал, пробуем URL (только для внешних URL)
            if (! empty($url) && ! str_contains($url, 'localhost') && ! str_starts_with($url, 'http://127.0.0.1')) {
                Log::info('MaxAdapter: Using external URL', ['url' => $url]);

                // Для изображений можно попробовать fromUrl
                if ($type === 'image') {
                    return \BushlanovDev\MaxMessengerBot\Models\Attachments\Requests\PhotoAttachmentRequest::fromUrl($url);
                }

                // Для остальных типов скачиваем и загружаем
                return $this->uploadAttachmentFromUrl($type, $url);
            }

            Log::warning('MaxAdapter: No valid attachment source found', [
                'type' => $type,
                'file_path' => $filePath,
                'url' => $url,
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Exception while preparing attachment', [
                'error' => $e->getMessage(),
                'attachment' => $attachment,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Загрузить файл в Max API
     *
     * @param  string  $type  - Тип файла (image, video, audio, file)
     * @param  string  $filePath  - Путь к файлу
     * @return \BushlanovDev\MaxMessengerBot\Models\Attachments\Requests\AbstractAttachmentRequest|null
     */
    protected function uploadAttachment(string $type, string $filePath)
    {
        try {
            // Определяем тип загрузки
            $uploadType = match ($type) {
                'image', 'photo' => \BushlanovDev\MaxMessengerBot\Enums\UploadType::Image,
                'video' => \BushlanovDev\MaxMessengerBot\Enums\UploadType::Video,
                'audio', 'voice' => \BushlanovDev\MaxMessengerBot\Enums\UploadType::Audio,
                default => \BushlanovDev\MaxMessengerBot\Enums\UploadType::File,
            };

            Log::info('MaxAdapter: Uploading attachment', [
                'type' => $type,
                'upload_type' => $uploadType->value,
                'file_path' => $filePath,
            ]);

            // Используем встроенный метод API для загрузки
            $attachmentRequest = $this->getApiClient()->uploadAttachment($uploadType, $filePath);

            Log::info('MaxAdapter: Attachment uploaded successfully', [
                'type' => $type,
            ]);

            return $attachmentRequest;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to upload attachment', [
                'type' => $type,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Скачать файл по URL и загрузить в Max API
     *
     * @param  string  $type  - Тип файла
     * @param  string  $url  - URL файла
     * @return \BushlanovDev\MaxMessengerBot\Models\Attachments\Requests\AbstractAttachmentRequest|null
     */
    protected function uploadAttachmentFromUrl(string $type, string $url)
    {
        try {
            Log::info('MaxAdapter: Downloading attachment from URL', [
                'type' => $type,
                'url' => $url,
            ]);

            // Скачиваем файл во временную директорию
            $tempPath = sys_get_temp_dir().'/'.uniqid('max_attachment_').'.'.pathinfo($url, PATHINFO_EXTENSION);

            $fileContent = file_get_contents($url);
            if ($fileContent === false) {
                throw new \RuntimeException('Failed to download file from URL');
            }

            file_put_contents($tempPath, $fileContent);

            // Загружаем файл
            $attachmentRequest = $this->uploadAttachment($type, $tempPath);

            // Удаляем временный файл
            @unlink($tempPath);

            return $attachmentRequest;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to download and upload attachment', [
                'type' => $type,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            // Очищаем временный файл в случае ошибки
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }

            return null;
        }
    }

    /**
     * Отметить сообщение как прочитанное в Max
     */
    public function markAsRead(string $externalId): bool
    {
        // Max API может не поддерживать mark as read
        // Возвращаем true чтобы не блокировать работу
        return true;
    }

    public function getSourceName(): string
    {
        return 'max';
    }

    /**
     * Отправить действие "печатает..." пользователю
     *
     * @param  int  $chatId  - ID чата
     */
    public function sendTypingAction(int $chatId): bool
    {
        try {
            $result = $this->getApiClient()->sendAction(
                $chatId,
                \BushlanovDev\MaxMessengerBot\Enums\SenderAction::TypingOn
            );

            return $result->success ?? false;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to send typing action', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить информацию о боте
     */
    public function getBotInfo(): ?\BushlanovDev\MaxMessengerBot\Models\BotInfo
    {
        try {
            return $this->getApiClient()->getBotInfo();

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to get bot info', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Получить информацию о чате
     *
     * @param  int  $chatId  - ID чата
     */
    public function getChat(int $chatId): ?\BushlanovDev\MaxMessengerBot\Models\Chat
    {
        try {
            return $this->getApiClient()->getChat($chatId);

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to get chat info', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Удалить сообщение
     *
     * @param  string  $messageId  - ID сообщения
     */
    public function deleteMessage(string $messageId): bool
    {
        try {
            $result = $this->getApiClient()->deleteMessage($messageId);

            return $result->success ?? false;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to delete message', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Редактировать сообщение
     *
     * @param  string  $messageId  - ID сообщения
     * @param  string|null  $text  - Новый текст
     * @param  array  $attachments  - Новые вложения
     */
    public function editMessage(string $messageId, ?string $text = null, array $attachments = []): bool
    {
        try {
            // Подготавливаем вложения
            $maxAttachments = [];
            if (! empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $maxAttachment = $this->prepareAttachment($attachment);
                    if ($maxAttachment) {
                        $maxAttachments[] = $maxAttachment;
                    }
                }
            }

            $result = $this->getApiClient()->editMessage(
                messageId: $messageId,
                text: $text,
                attachments: ! empty($maxAttachments) ? $maxAttachments : null
            );

            return $result->success ?? false;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to edit message', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить сообщения из чата
     *
     * @param  int  $chatId  - ID чата
     * @param  int|null  $count  - Количество сообщений
     * @param  int|null  $from  - Начальная временная метка (Unix timestamp в мс)
     * @param  int|null  $to  - Конечная временная метка (Unix timestamp в мс)
     */
    public function getMessages(int $chatId, ?int $count = null, ?int $from = null, ?int $to = null): array
    {
        try {
            return $this->getApiClient()->getMessages(
                chatId: $chatId,
                count: $count,
                from: $from,
                to: $to
            );

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to get messages', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Закрепить сообщение в чате
     *
     * @param  int  $chatId  - ID чата
     * @param  string  $messageId  - ID сообщения
     * @param  bool  $notify  - Уведомить участников
     */
    public function pinMessage(int $chatId, string $messageId, bool $notify = true): bool
    {
        try {
            $result = $this->getApiClient()->pinMessage($chatId, $messageId, $notify);

            return $result->success ?? false;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to pin message', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Открепить сообщение в чате
     *
     * @param  int  $chatId  - ID чата
     */
    public function unpinMessage(int $chatId): bool
    {
        try {
            $result = $this->getApiClient()->unpinMessage($chatId);

            return $result->success ?? false;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to unpin message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить список чатов
     *
     * @param  int|null  $count  - Количество чатов (1-100)
     * @param  int|null  $marker  - Маркер для пагинации
     */
    public function getChats(?int $count = null, ?int $marker = null): ?\BushlanovDev\MaxMessengerBot\Models\ChatList
    {
        try {
            return $this->getApiClient()->getChats($count, $marker);

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to get chats', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Покинуть чат
     *
     * @param  int  $chatId  - ID чата
     */
    public function leaveChat(int $chatId): bool
    {
        try {
            $result = $this->getApiClient()->leaveChat($chatId);

            return $result->success ?? false;

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to leave chat', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить информацию о членстве бота в чате
     *
     * @param  int  $chatId  - ID чата
     */
    public function getMembership(int $chatId): ?\BushlanovDev\MaxMessengerBot\Models\ChatMember
    {
        try {
            return $this->getApiClient()->getMembership($chatId);

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to get membership', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Получить участников чата
     *
     * @param  int  $chatId  - ID чата
     * @param  array|null  $userIds  - Список ID пользователей
     * @param  int|null  $marker  - Маркер для пагинации
     * @param  int|null  $count  - Количество участников (1-100)
     */
    public function getMembers(int $chatId, ?array $userIds = null, ?int $marker = null, ?int $count = null): ?\BushlanovDev\MaxMessengerBot\Models\ChatMembersList
    {
        try {
            return $this->getApiClient()->getMembers($chatId, $userIds, $marker, $count);

        } catch (\Exception $e) {
            Log::error('MaxAdapter: Failed to get members', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
