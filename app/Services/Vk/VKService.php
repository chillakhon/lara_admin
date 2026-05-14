<?php

namespace App\Services\Vk;

use App\Models\Conversation;
use App\Models\Client;
use App\Models\VKSettings;
use App\Services\File\FileStorageService;
use App\Services\Messaging\ConversationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class VKService
{
    protected ConversationService $conversationService;
    protected FileStorageService $fileStorage;

    public function __construct(
        ConversationService $conversationService,
        FileStorageService  $fileStorage
    )
    {
        $this->conversationService = $conversationService;
        $this->fileStorage = $fileStorage;
    }

    /**
     * Обработать входящий webhook от ВК
     */
    public function handleWebhookUpdate(array $update): array
    {
        try {
            if (!$this->validateUpdate($update)) {
                Log::warning("VKService: Invalid webhook update", ['update' => $update]);
                return ['ok' => false];
            }

            if ($update['type'] === 'confirmation') {
                return $this->handleConfirmation();
            }

            if ($update['type'] === 'message_new') {
                return $this->handleMessageNew($update['object']);
            }

            return ['ok' => true];

        } catch (\Exception $e) {
            Log::error("VKService: Exception in handleWebhookUpdate", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['ok' => false];
        }
    }

    protected function validateUpdate(array $update): bool
    {
        return isset($update['type']) && isset($update['group_id']);
    }

    protected function handleConfirmation(): array
    {
        $settings = VKSettings::first();

        if (!$settings || !$settings->confirmation_token) {
            Log::error("VKService: Confirmation token not found");
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'confirmation_token' => $settings->confirmation_token
        ];
    }

    /**
     * Обработать новое сообщение из ВК
     */
    protected function handleMessageNew(array $messageObject): array
    {
        return DB::transaction(function () use ($messageObject) {
            $message = $messageObject['message'] ?? null;

            if (!$message) {
                Log::warning("VKService: Message object not found", ['object' => $messageObject]);
                return ['ok' => false];
            }

            $userId = $message['from_id'] ?? null;
            $text = $message['text'] ?? '';
            $messageId = $message['id'] ?? null;
            $peerId = $message['peer_id'] ?? null;

            if (!$userId) {
                Log::warning("VKService: Message without user_id", ['message' => $message]);
                return ['ok' => false];
            }

            $client = $this->findClient($userId);

            $conversation = Conversation::firstOrCreate(
                [
                    'source' => 'vk',
                    'external_id' => (string)$userId,
                    'client_id' => $client?->id ?? null,
                ],
                [
                    'status' => 'active',
                    'last_message_at' => now(),
                    'unread_messages_count' => 0,
                ]
            );

            $messageData = [
                'direction' => 'incoming',
                'content' => $text,
                'content_type' => 'text',
                'status' => 'delivered',
                'source_data' => [
                    'vk_message_id' => $messageId,
                    'vk_user_id' => $userId,
                    'vk_peer_id' => $peerId,
                    'raw_attachments' => $message['attachments'] ?? [],
                ]
            ];

            if (!empty($message['attachments'])) {
                $attachments = $this->processAttachments($message['attachments']);
                if (!empty($attachments)) {
                    $messageData['attachments'] = $attachments;
                }
            }

            $this->conversationService->addMessage($conversation, $messageData);

            event(new \App\Events\ConversationUpdated($conversation));

            return ['ok' => true];
        });
    }

    protected function findClient(int $vkUserId): ?Client
    {
        try {
            $client = Client::whereHas('profile', function ($query) use ($vkUserId) {
                $query->where('vk_user_id', $vkUserId);
            })->first();

            if ($client) {
                return $client;
            } else {
                return null;
            }

        } catch (\Exception $e) {
            Log::error("VKService: Failed to create client", [
                'vk_user_id' => $vkUserId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Обработать вложения из ВК (скачать и сохранить)
     */
    protected function processAttachments(array $attachments): array
    {
        $processed = [];

        foreach ($attachments as $attachment) {
            $type = $attachment['type'] ?? null;

            try {
                $downloadedFile = null;

                switch ($type) {
                    case 'photo':
                        $downloadedFile = $this->downloadPhoto($attachment);
                        break;

                    case 'doc':
                        $downloadedFile = $this->downloadDocument($attachment);
                        break;

                    case 'audio_message':
                        $downloadedFile = $this->downloadAudioMessage($attachment);
                        break;
                }

                if ($downloadedFile) {
                    $processed[] = $downloadedFile;
                }

            } catch (\Exception $e) {
                Log::error("VKService: Failed to process attachment", [
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processed;
    }

    /**
     * Скачать фото из VK
     */
    protected function downloadPhoto(array $attachment): ?array
    {
        try {
            if (!isset($attachment['photo']['sizes'])) {
                return null;
            }

            // Берем самый большой размер
            $sizes = $attachment['photo']['sizes'];
            $largest = end($sizes);
            $url = $largest['url'] ?? null;

            if (!$url) {
                return null;
            }

            return $this->downloadAndSaveFile($url, 'photo.jpg', 'image/jpeg', 'image');

        } catch (\Exception $e) {
            Log::error("VKService: Failed to download photo", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Скачать документ из VK
     */
    protected function downloadDocument(array $attachment): ?array
    {
        try {
            if (!isset($attachment['doc']['url'])) {
                return null;
            }

            $url = $attachment['doc']['url'];
            $title = $attachment['doc']['title'] ?? 'document';
            $ext = $attachment['doc']['ext'] ?? 'bin';
            $fileName = $title . '.' . $ext;

            // Определяем тип по расширению
            $type = $this->getTypeFromExtension($ext);
            $mimeType = $this->guessMimeType($ext);

            return $this->downloadAndSaveFile($url, $fileName, $mimeType, $type);

        } catch (\Exception $e) {
            Log::error("VKService: Failed to download document", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Скачать голосовое сообщение из VK
     */
    protected function downloadAudioMessage(array $attachment): ?array
    {
        try {
            if (!isset($attachment['audio_message']['link_ogg'])) {
                return null;
            }

            $url = $attachment['audio_message']['link_ogg'];
            $fileName = 'voice_' . time() . '.ogg';

            return $this->downloadAndSaveFile($url, $fileName, 'audio/ogg', 'audio');

        } catch (\Exception $e) {
            Log::error("VKService: Failed to download audio message", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Скачать файл по URL и сохранить в storage
     */
    protected function downloadAndSaveFile(string $url, string $fileName, string $mimeType, string $type): ?array
    {
        try {
            Log::info("VKService: Downloading file from VK", [
                'url' => $url,
                'file_name' => $fileName
            ]);

            // Скачиваем файл
            $response = Http::get($url);

            if (!$response->successful()) {
                Log::error("VKService: Failed to download file", [
                    'url' => $url,
                    'status' => $response->status()
                ]);
                return null;
            }

            $fileContent = $response->body();

            if (!$fileContent) {
                return null;
            }

            // Определяем расширение
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            if (!$extension) {
                $extension = $this->guessExtensionFromMime($mimeType);
            }

            // Генерируем имя файла
            $hash = md5(time() . uniqid());
            $storedFileName = $hash . '.' . $extension;
            $directory = 'chat-attachments/' . now()->format('Y/m');
            $filePath = $directory . '/' . $storedFileName;

            // Сохраняем в storage
            Storage::disk('public')->put($filePath, $fileContent);

            Log::info("VKService: File saved successfully", [
                'file_path' => $filePath,
                'size' => strlen($fileContent)
            ]);

            // Возвращаем данные в формате для БД
            return [
                'type' => $type,
                'url' => url('storage/' . $filePath),
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => strlen($fileContent),
                'mime_type' => $mimeType,
            ];

        } catch (\Exception $e) {
            Log::error("VKService: Exception while downloading file", [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Определить тип вложения по расширению
     */
    protected function getTypeFromExtension(string $extension): string
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $audioExtensions = ['mp3', 'ogg', 'wav', 'm4a', 'oga'];

        if (in_array(strtolower($extension), $imageExtensions)) {
            return 'image';
        }

        if (in_array(strtolower($extension), $audioExtensions)) {
            return 'audio';
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
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
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
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'application/pdf' => 'pdf',
        ];

        return $mimeMap[$mimeType] ?? 'bin';
    }
}
