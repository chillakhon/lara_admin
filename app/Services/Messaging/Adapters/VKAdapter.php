<?php

namespace App\Services\Messaging\Adapters;

use App\Models\VKSettings;
use App\Models\Message;
use App\Services\Messaging\AbstractMessageAdapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VKAdapter extends AbstractMessageAdapter
{
    protected VKSettings $settings;

    public function __construct()
    {
        $this->settings = VKSettings::first();

        if (!$this->settings) {
            Log::error("VKAdapter: VKSettings not found in database");
            throw new \RuntimeException("ВК settings не найдены в БД. Сначала настрой ВК интеграцию в админке.");
        }

        if (!$this->settings->access_token) {
            Log::error("VKAdapter: access_token is empty");
            throw new \RuntimeException("Access token не установлен");
        }

//        Log::info("VKAdapter initialized", [
//            'community_id' => $this->settings->community_id,
//            'api_version' => $this->settings->api_version
//        ]);
    }

    /**
     * Отправить сообщение в ВК
     * @param string $externalId - ID пользователя ВК (peer_id)
     * @param string $content - Текст сообщения
     * @param array $attachments - Вложения
     * @return bool
     */
    public function sendMessage(string $externalId, ?string $content, array $attachments = []): bool
    {
        try {
            // Загружаем вложения и получаем attachment_ids
            $attachmentStrings = [];

            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $attachmentId = $this->uploadAttachment($externalId, $attachment);
                    if ($attachmentId) {
                        $attachmentStrings[] = $attachmentId;
                    }
                }
            }

            // Формируем параметры запроса
            $params = [
                'peer_id' => $externalId,
                'random_id' => random_int(1, 999999999),
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ];

            // Добавляем текст если есть
            if (!empty($content)) {
                $params['message'] = $content;
            }

            // Добавляем вложения если есть
            if (!empty($attachmentStrings)) {
                $params['attachment'] = implode(',', $attachmentStrings);
            }

            Log::info("VKAdapter: Sending message", [
                'peer_id' => $externalId,
                'has_content' => !empty($content),
                'attachments_count' => count($attachmentStrings),
                'params' => $params
            ]);

            $response = Http::asForm()->post('https://api.vk.com/method/messages.send', $params);

            if (!$response->successful()) {
                Log::error("VKAdapter: HTTP request failed", [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }

            $data = $response->json();

            if (isset($data['error'])) {
                $error = $data['error']['error_msg'] ?? 'Unknown error';
                Log::error("VKAdapter: Failed to send message", [
                    'peer_id' => $externalId,
                    'error' => $error,
                    'error_code' => $data['error']['error_code'] ?? null
                ]);
                return false;
            }

            Log::info("VKAdapter: Message sent successfully", [
                'peer_id' => $externalId,
                'response' => $data
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("VKAdapter: Exception while sending message", [
                'peer_id' => $externalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Загрузить вложение в VK и получить attachment_id
     */
    protected function uploadAttachment(string $peerId, array $attachment): ?string
    {
        try {
            $type = $attachment['type'] ?? 'file';
            $filePath = $attachment['file_path'] ?? null;

            if (!$filePath) {
                Log::error("VKAdapter: file_path not found in attachment");
                return null;
            }

            // Получаем полный путь к файлу
            $fullPath = Storage::disk('public')->path($filePath);

            if (!file_exists($fullPath)) {
                Log::error("VKAdapter: File not found", [
                    'file_path' => $filePath,
                    'full_path' => $fullPath
                ]);
                return null;
            }

            Log::info("VKAdapter: Uploading attachment", [
                'type' => $type,
                'file_path' => $filePath
            ]);

            // В зависимости от типа используем разные методы загрузки
            switch ($type) {
                case 'image':
                    return $this->uploadPhoto($peerId, $fullPath);

                case 'audio':
                    return $this->uploadAudioMessage($peerId, $fullPath);

                case 'file':
                default:
                    return $this->uploadDocument($peerId, $fullPath);
            }

        } catch (\Exception $e) {
            Log::error("VKAdapter: Failed to upload attachment", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Загрузить фото в VK
     */
    protected function uploadPhoto(string $peerId, string $filePath): ?string
    {
        try {
            // 1. Получаем upload URL
            $uploadServerResponse = Http::get('https://api.vk.com/method/photos.getMessagesUploadServer', [
                'peer_id' => $peerId,
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ])->json();

            if (isset($uploadServerResponse['error'])) {
                Log::error("VKAdapter: Failed to get photo upload server", [
                    'error' => $uploadServerResponse['error']
                ]);
                return null;
            }

            $uploadUrl = $uploadServerResponse['response']['upload_url'] ?? null;
            if (!$uploadUrl) {
                return null;
            }

            // 2. Загружаем файл на upload_url
            $uploadResponse = Http::attach(
                'photo',
                file_get_contents($filePath),
                basename($filePath)
            )->post($uploadUrl)->json();

            if (!isset($uploadResponse['photo']) || !isset($uploadResponse['server']) || !isset($uploadResponse['hash'])) {
                Log::error("VKAdapter: Invalid upload response", [
                    'response' => $uploadResponse
                ]);
                return null;
            }

            // 3. Сохраняем фото
            $saveResponse = Http::get('https://api.vk.com/method/photos.saveMessagesPhoto', [
                'photo' => $uploadResponse['photo'],
                'server' => $uploadResponse['server'],
                'hash' => $uploadResponse['hash'],
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ])->json();

            if (isset($saveResponse['error']) || !isset($saveResponse['response'][0])) {
                Log::error("VKAdapter: Failed to save photo", [
                    'error' => $saveResponse['error'] ?? 'Unknown error'
                ]);
                return null;
            }

            $photo = $saveResponse['response'][0];
            $attachmentId = "photo{$photo['owner_id']}_{$photo['id']}";

            Log::info("VKAdapter: Photo uploaded successfully", [
                'attachment_id' => $attachmentId
            ]);

            return $attachmentId;

        } catch (\Exception $e) {
            Log::error("VKAdapter: Exception while uploading photo", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Загрузить голосовое сообщение в VK
     */
    protected function uploadAudioMessage(string $peerId, string $filePath): ?string
    {
        try {
            // 1. Получаем upload URL для голосового сообщения
            $uploadServerResponse = Http::get('https://api.vk.com/method/docs.getMessagesUploadServer', [
                'type' => 'audio_message',
                'peer_id' => $peerId,
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ])->json();

            if (isset($uploadServerResponse['error'])) {
                Log::error("VKAdapter: Failed to get audio upload server", [
                    'error' => $uploadServerResponse['error']
                ]);
                return null;
            }

            $uploadUrl = $uploadServerResponse['response']['upload_url'] ?? null;
            if (!$uploadUrl) {
                return null;
            }

            // 2. Загружаем файл
            $uploadResponse = Http::attach(
                'file',
                file_get_contents($filePath),
                basename($filePath)
            )->post($uploadUrl)->json();

            if (!isset($uploadResponse['file'])) {
                Log::error("VKAdapter: Invalid audio upload response", [
                    'response' => $uploadResponse
                ]);
                return null;
            }

            // 3. Сохраняем документ
            $saveResponse = Http::get('https://api.vk.com/method/docs.save', [
                'file' => $uploadResponse['file'],
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ])->json();

            if (isset($saveResponse['error']) || !isset($saveResponse['response']['audio_message'])) {
                Log::error("VKAdapter: Failed to save audio message", [
                    'error' => $saveResponse['error'] ?? 'Unknown error'
                ]);
                return null;
            }

            $doc = $saveResponse['response']['audio_message'];
            $attachmentId = "doc{$doc['owner_id']}_{$doc['id']}";

            Log::info("VKAdapter: Audio message uploaded successfully", [
                'attachment_id' => $attachmentId
            ]);

            return $attachmentId;

        } catch (\Exception $e) {
            Log::error("VKAdapter: Exception while uploading audio", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Загрузить документ в VK
     */
    protected function uploadDocument(string $peerId, string $filePath): ?string
    {
        try {
            // 1. Получаем upload URL для документа
            $uploadServerResponse = Http::get('https://api.vk.com/method/docs.getMessagesUploadServer', [
                'type' => 'doc',
                'peer_id' => $peerId,
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ])->json();

            if (isset($uploadServerResponse['error'])) {
                Log::error("VKAdapter: Failed to get doc upload server", [
                    'error' => $uploadServerResponse['error']
                ]);
                return null;
            }

            $uploadUrl = $uploadServerResponse['response']['upload_url'] ?? null;
            if (!$uploadUrl) {
                return null;
            }

            // 2. Загружаем файл
            $uploadResponse = Http::attach(
                'file',
                file_get_contents($filePath),
                basename($filePath)
            )->post($uploadUrl)->json();

            if (!isset($uploadResponse['file'])) {
                Log::error("VKAdapter: Invalid doc upload response", [
                    'response' => $uploadResponse
                ]);
                return null;
            }

            // 3. Сохраняем документ
            $saveResponse = Http::get('https://api.vk.com/method/docs.save', [
                'file' => $uploadResponse['file'],
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ])->json();

            if (isset($saveResponse['error']) || !isset($saveResponse['response']['doc'])) {
                Log::error("VKAdapter: Failed to save document", [
                    'error' => $saveResponse['error'] ?? 'Unknown error'
                ]);
                return null;
            }

            $doc = $saveResponse['response']['doc'];
            $attachmentId = "doc{$doc['owner_id']}_{$doc['id']}";

            Log::info("VKAdapter: Document uploaded successfully", [
                'attachment_id' => $attachmentId
            ]);

            return $attachmentId;

        } catch (\Exception $e) {
            Log::error("VKAdapter: Exception while uploading document", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Отметить сообщение как прочитанное в ВК
     */
    public function markAsRead(string $externalId): bool
    {
        try {
            $response = Http::post('https://api.vk.com/method/messages.markAsRead', [
                'peer_id' => $externalId,
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ]);

            return $response->successful() && !isset($response['error']);

        } catch (\Exception $e) {
            Log::error("VKAdapter: Failed to mark as read", [
                'peer_id' => $externalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getSourceName(): string
    {
        return 'vk';
    }
}
