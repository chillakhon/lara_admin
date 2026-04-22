<?php

namespace App\Services\Max;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\MaxSettings;
use App\Services\File\FileStorageService;
use App\Services\Messaging\ConversationService;
use BushlanovDev\MaxMessengerBot\Api;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MaxService
{
    protected ConversationService $conversationService;

    protected FileStorageService $fileStorage;

    protected ?Api $apiClient = null;

    public function __construct(
        ConversationService $conversationService,
        FileStorageService $fileStorage
    ) {
        $this->conversationService = $conversationService;
        $this->fileStorage = $fileStorage;
    }

    /**
     * Получить API клиент Max
     */
    public function getApiClient(): Api
    {
        if (! $this->apiClient) {
            $settings = MaxSettings::where('is_active', true)->first();

            if (! $settings) {
                throw new \Exception('Max settings not found');
            }

            $this->apiClient = new Api($settings->bot_token);
        }

        return $this->apiClient;
    }

    /**
     * Получить полный URL для webhook
     * Автоматически добавляет /api/public/max/webhook к базовому URL
     */
    protected function getWebhookUrl(): string
    {
        $baseUrl = config('services.max.webhook_url');

        // Убираем trailing slash если есть
        $baseUrl = rtrim($baseUrl, '/');

        // Добавляем /api/public/max/webhook
        return $baseUrl.'/api/public/max/webhook';
    }

    /**
     * Получить secret для webhook
     */
    protected function getWebhookSecret(): ?string
    {
        return config('services.max.webhook_secret');
    }

    /**
     * Получить список webhook подписок
     */
    public function getWebhookSubscriptions(): array
    {
        try {
            $client = $this->getApiClient();

            return $client->getSubscriptions();
        } catch (\Exception $e) {
            Log::error('Failed to get Max subscriptions', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Проверить, существует ли уже подписка на данный URL
     */
    public function hasWebhookSubscription(string $url): bool
    {
        try {
            $subscriptions = $this->getWebhookSubscriptions();

            foreach ($subscriptions as $subscription) {
                if (isset($subscription->url) && $subscription->url === $url) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to check webhook subscription', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Зарегистрировать webhook с проверкой существующей подписки
     */
    public function registerWebhookIfNeeded(): array
    {
        try {
            $url = $this->getWebhookUrl();
            $secret = $this->getWebhookSecret();

            // Проверяем, есть ли уже подписка
            if ($this->hasWebhookSubscription($url)) {
                Log::info('Max webhook already registered', ['url' => $url]);

                return [
                    'success' => true,
                    'message' => 'Webhook already registered',
                    'url' => $url,
                    'already_exists' => true,
                ];
            }

            // Регистрируем новую подписку
            $client = $this->getApiClient();
            $result = $client->subscribe($url, $secret);

            Log::info('Max webhook registered successfully', [
                'url' => $url,
                'result' => $result,
            ]);

            return [
                'success' => true,
                'message' => 'Webhook registered successfully',
                'url' => $url,
                'result' => $result,
                'already_exists' => false,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to register Max webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Удалить webhook подписку
     */
    public function unregisterWebhook(string $url): array
    {
        try {
            $client = $this->getApiClient();
            $result = $client->unsubscribe($url);

            return [
                'success' => true,
                'result' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Главный обработчик webhook от Max
     */
    public function handleWebhookUpdate(array $update): array
    {
        try {
            $updateType = $update['update_type'] ?? null;

            Log::info('Max webhook update received', [
                'type' => $updateType,
                'update' => $update,
            ]);

            switch ($updateType) {
                case 'message_created':
                    return $this->handleMessageCreated($update['message'] ?? []);

                case 'message_edited':
                    return $this->handleMessageEdited($update['message'] ?? []);

                case 'message_deleted':
                    return $this->handleMessageDeleted($update['message'] ?? []);

                case 'message_read':
                    return $this->handleMessageRead($update);

                case 'bot_started':
                    return $this->handleBotStarted($update);

                default:
                    Log::info('MaxService: Unknown update type', ['type' => $updateType]);

                    return ['ok' => true];
            }

        } catch (\Exception $e) {
            Log::error('MaxService: Exception in handleWebhookUpdate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['ok' => false];
        }
    }

    /**
     * Обработка нового сообщения
     */
    protected function handleMessageCreated(array $message): array
    {
        return DB::transaction(function () use ($message) {
            // Извлекаем данные
            $senderId = $message['sender']['user_id'] ?? null;
            $senderName = $message['sender']['name'] ?? '';
            $chatId = $message['recipient']['chat_id'] ?? null;
            $messageId = $message['body']['mid'] ?? null;
            $text = $message['body']['text'] ?? '';
            $attachments = $message['body']['attachments'] ?? [];

            if (! $senderId) {
                Log::warning('MaxService: Message without sender_id');

                return ['ok' => false];
            }

            // Находим клиента
            $client = $this->findClient($senderId);

            // Создаем/обновляем conversation
            $conversation = Conversation::firstOrCreate(
                [
                    'source' => 'max',
                    'external_id' => (string) $senderId,
                    'client_id' => $client?->id ?? null,
                ],
                [
                    'status' => 'active',
                    'last_message_at' => now(),
                    'unread_messages_count' => 0,
                ]
            );

            // Подготавливаем данные сообщения
            $messageData = [
                'direction' => 'incoming',
                'content' => $text,
                'content_type' => 'text',
                'status' => 'delivered',
                'source_data' => [
                    'max_message_id' => $messageId,
                    'max_user_id' => $senderId,
                    'max_chat_id' => $chatId,
                    'sender_name' => $senderName,
                    'raw_attachments' => $attachments,
                ],
            ];

            // Обрабатываем вложения
            if (! empty($attachments)) {
                $processedAttachments = $this->processAttachments($attachments);
                if (! empty($processedAttachments)) {
                    $messageData['attachments'] = $processedAttachments;
                }
            }

            // Если нет текста и вложений не удалось скачать, добавляем placeholder
            if (empty($messageData['content']) && empty($messageData['attachments'])) {
                // Определяем тип вложения для placeholder
                $attachmentType = $attachments[0]['type'] ?? 'file';
                $messageData['content'] = '['.ucfirst($attachmentType).' attachment - failed to download]';

                Log::warning('MaxService: Failed to download attachments, using placeholder', [
                    'attachment_type' => $attachmentType,
                    'attachments_count' => count($attachments),
                ]);
            }

            // Сохраняем сообщение
            $this->conversationService->addMessage($conversation, $messageData);

            // Отправляем событие
            event(new \App\Events\ConversationUpdated($conversation));

            return ['ok' => true];
        });
    }

    /**
     * Обработка вложений Max
     */
    protected function processAttachments(array $attachments): array
    {
        $processed = [];

        foreach ($attachments as $attachment) {
            $type = $attachment['type'] ?? null;

            try {
                $downloadedFile = null;

                switch ($type) {
                    case 'image':
                        $downloadedFile = $this->downloadImage($attachment);
                        break;

                    case 'file':
                        $downloadedFile = $this->downloadFile($attachment);
                        break;

                    case 'video':
                        $downloadedFile = $this->downloadVideo($attachment);
                        break;

                    case 'audio':
                        $downloadedFile = $this->downloadAudio($attachment);
                        break;
                }

                if ($downloadedFile) {
                    $processed[] = $downloadedFile;
                }

            } catch (\Exception $e) {
                Log::error('MaxService: Failed to process attachment', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Скачать изображение
     */
    protected function downloadImage(array $attachment): ?array
    {
        $url = $attachment['payload']['url'] ?? null;
        $photoId = $attachment['payload']['photo_id'] ?? null;

        if (! $url) {
            return null;
        }

        $fileName = 'photo_'.$photoId.'.jpg';

        return $this->downloadAndSaveFile($url, $fileName, 'image/jpeg', 'image');
    }

    /**
     * Скачать файл
     */
    protected function downloadFile(array $attachment): ?array
    {
        $url = $attachment['payload']['url'] ?? null;
        $fileName = $attachment['filename'] ?? 'file.bin';

        if (! $url) {
            return null;
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeType = $this->guessMimeType($extension);
        $type = $this->getTypeFromExtension($extension);

        return $this->downloadAndSaveFile($url, $fileName, $mimeType, $type);
    }

    /**
     * Скачать видео
     */
    protected function downloadVideo(array $attachment): ?array
    {
        $url = $attachment['payload']['url'] ?? null;
        $videoId = $attachment['payload']['id'] ?? null;
        $duration = $attachment['duration'] ?? null;
        $thumbnail = $attachment['thumbnail']['url'] ?? null;

        if (! $url) {
            Log::warning('MaxService: Video URL is missing', ['attachment' => $attachment]);

            return null;
        }

        // Используем ID видео для имени файла, если доступен
        $fileName = $videoId ? 'video_'.$videoId.'.mp4' : 'video_'.time().'.mp4';

        Log::info('MaxService: Downloading video', [
            'url' => $url,
            'video_id' => $videoId,
            'duration' => $duration,
            'has_thumbnail' => ! empty($thumbnail),
        ]);

        $result = $this->downloadAndSaveFile($url, $fileName, 'video/mp4', 'video');

        // Добавляем дополнительные метаданные видео
        if ($result) {
            $result['duration'] = $duration;
            $result['thumbnail_url'] = $thumbnail;
            $result['video_id'] = $videoId;
        }

        return $result;
    }

    /**
     * Скачать аудио
     */
    protected function downloadAudio(array $attachment): ?array
    {
        $url = $attachment['payload']['url'] ?? null;
        $fileName = 'audio_'.time().'.mp3';

        if (! $url) {
            return null;
        }

        return $this->downloadAndSaveFile($url, $fileName, 'audio/mpeg', 'audio');
    }

    /**
     * Скачать и сохранить файл
     */
    protected function downloadAndSaveFile(string $url, string $fileName, string $mimeType, string $type): ?array
    {
        try {
            Log::info('MaxService: Downloading file from Max', [
                'url' => $url,
                'file_name' => $fileName,
                'type' => $type,
            ]);

            // Увеличиваем timeout для видео файлов (они могут быть большими)
            $timeout = $type === 'video' ? 120 : 30;
            $connectTimeout = 30; // Увеличиваем connect timeout

            // Скачиваем файл с retry (URL может иметь expires, поэтому скачиваем сразу)
            $maxRetries = 2;
            $response = null;
            $fileContent = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = Http::timeout($timeout)
                        ->connectTimeout($connectTimeout)
                        ->retry(2, 1000) // 2 retry с задержкой 1 сек
                        ->get($url);

                    if ($response->successful()) {
                        $fileContent = $response->body();
                        break;
                    }

                    Log::warning('MaxService: Download attempt failed', [
                        'attempt' => $attempt,
                        'status' => $response->status(),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('MaxService: Download attempt exception (trying curl fallback)', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    // Fallback: пробуем через системный curl (он использует системный DNS)
                    if (str_contains($e->getMessage(), 'Could not resolve host')) {
                        Log::info('MaxService: Trying curl fallback for DNS issue');
                        $fileContent = $this->downloadViaCurl($url, $timeout);
                        if ($fileContent) {
                            Log::info('MaxService: Curl fallback successful');
                            break;
                        }
                    }

                    if ($attempt === $maxRetries) {
                        throw $e;
                    }

                    sleep(2); // Ждем перед следующей попыткой
                }
            }

            if (! $fileContent) {
                Log::error('MaxService: Failed to download file after retries', [
                    'url' => $url,
                    'status' => $response ? $response->status() : 'no response',
                    'type' => $type,
                ]);

                return null;
            }

            if (! $fileContent) {
                Log::warning('MaxService: Downloaded file is empty', ['url' => $url]);

                return null;
            }

            // Генерируем путь
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            if (! $extension) {
                $extension = $this->guessExtensionFromMime($mimeType);
            }

            $hash = md5(time().uniqid());
            $storedFileName = $hash.'.'.$extension;
            $directory = 'chat-attachments/'.now()->format('Y/m');
            $filePath = $directory.'/'.$storedFileName;

            // Сохраняем
            Storage::disk('public')->put($filePath, $fileContent);

            Log::info('MaxService: File saved successfully', [
                'file_path' => $filePath,
                'size' => strlen($fileContent),
                'type' => $type,
            ]);

            return [
                'type' => $type,
                'url' => url('storage/'.$filePath),
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => strlen($fileContent),
                'mime_type' => $mimeType,
            ];

        } catch (\Exception $e) {
            Log::error('MaxService: Exception while downloading file', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Скачать файл через системный curl (fallback для DNS проблем)
     */
    protected function downloadViaCurl(string $url, int $timeout = 30): ?string
    {
        try {
            // Создаем временный файл для сохранения
            $tempFile = tempnam(sys_get_temp_dir(), 'max_curl_');

            // Экранируем URL для безопасности
            $escapedUrl = escapeshellarg($url);
            $escapedFile = escapeshellarg($tempFile);

            // Пытаемся извлечь хост из URL для DNS resolve
            $parsedUrl = parse_url($url);
            $host = $parsedUrl['host'] ?? null;
            $resolveOption = '';

            // Если хост vd753.okcdn.ru, используем известный IP
            if ($host === 'vd753.okcdn.ru') {
                $resolveOption = '--resolve "vd753.okcdn.ru:443:95.163.35.30"';
                Log::info('MaxService: Using DNS resolve for vd753.okcdn.ru');
            }

            // Выполняем curl с системным DNS resolver и resolve option
            $command = sprintf(
                'curl -L -s -S %s --connect-timeout 30 --max-time %d -o %s %s 2>&1',
                $resolveOption,
                $timeout,
                $escapedFile,
                $escapedUrl
            );

            Log::info('MaxService: Curl command prepared', [
                'command' => $command,
                'host' => $host,
            ]);

            Log::info('MaxService: Executing curl command', [
                'timeout' => $timeout,
                'has_resolve' => ! empty($resolveOption),
                'host' => $host,
            ]);

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error('MaxService: Curl command failed', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output),
                ]);
                @unlink($tempFile);

                return null;
            }

            // Читаем содержимое файла
            if (! file_exists($tempFile) || filesize($tempFile) === 0) {
                Log::error('MaxService: Downloaded file is empty or missing');
                @unlink($tempFile);

                return null;
            }

            $fileContent = file_get_contents($tempFile);
            @unlink($tempFile);

            Log::info('MaxService: Curl download successful', [
                'size' => strlen($fileContent),
            ]);

            return $fileContent;

        } catch (\Exception $e) {
            Log::error('MaxService: Exception in downloadViaCurl', [
                'error' => $e->getMessage(),
            ]);

            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }

            return null;
        }
    }

    /**
     * Найти клиента по max_user_id
     */
    protected function findClient(int $maxUserId): ?Client
    {
        try {
            return Client::whereHas('profile', function ($query) use ($maxUserId) {
                $query->where('max_user_id', $maxUserId);
            })->first();
        } catch (\Exception $e) {
            Log::error('MaxService: Failed to find client', [
                'max_user_id' => $maxUserId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Отправить сообщение через Max API
     */
    public function sendMessage(int $userId, string $text, array $options = []): ?array
    {
        try {
            $client = $this->getApiClient();

            $result = $client->sendUserMessage($userId, $text);

            return $result->toArray();

        } catch (\Exception $e) {
            Log::error('MaxService: Failed to send message', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Обработка редактирования сообщения
     */
    protected function handleMessageEdited(array $message): array
    {
        Log::info('MaxService: Message edited', ['message' => $message]);

        // TODO: Implement message editing logic if needed
        return ['ok' => true];
    }

    /**
     * Обработка удаления сообщения
     */
    protected function handleMessageDeleted(array $message): array
    {
        Log::info('MaxService: Message deleted', ['message' => $message]);

        // TODO: Implement message deletion logic if needed
        return ['ok' => true];
    }

    /**
     * Обработка прочтения сообщения
     */
    protected function handleMessageRead(array $update): array
    {
        Log::info('MaxService: Message read', ['update' => $update]);

        // TODO: Implement message read status logic if needed
        return ['ok' => true];
    }

    /**
     * Обработка запуска бота
     */
    protected function handleBotStarted(array $update): array
    {
        Log::info('MaxService: Bot started', ['update' => $update]);

        // TODO: Implement bot started logic if needed
        return ['ok' => true];
    }

    /**
     * Определить тип вложения по расширению
     */
    protected function getTypeFromExtension(string $extension): string
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $audioExtensions = ['mp3', 'ogg', 'wav', 'm4a', 'oga'];
        $videoExtensions = ['mp4', 'avi', 'mov', 'webm'];

        if (in_array(strtolower($extension), $imageExtensions)) {
            return 'image';
        }

        if (in_array(strtolower($extension), $audioExtensions)) {
            return 'audio';
        }

        if (in_array(strtolower($extension), $videoExtensions)) {
            return 'video';
        }

        return 'file';
    }

    /**
     * Угадать MIME тип по расширению
     */
    protected function guessMimeType(string $extension): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ovpn' => 'application/x-openvpn-profile',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Угадать расширение по MIME типу
     */
    protected function guessExtensionFromMime(string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'video/mp4' => 'mp4',
            'video/x-msvideo' => 'avi',
            'video/quicktime' => 'mov',
            'application/pdf' => 'pdf',
        ];

        return $mimeMap[$mimeType] ?? 'bin';
    }
}
