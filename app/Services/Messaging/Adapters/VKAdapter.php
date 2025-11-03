<?php

namespace App\Services\Messaging\Adapters;

use App\Models\VKSettings;
use App\Models\Message;
use App\Services\Messaging\AbstractMessageAdapter;
use Illuminate\Support\Facades\Http;
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

        Log::info("VKAdapter initialized", [
            'community_id' => $this->settings->community_id,
            'api_version' => $this->settings->api_version
        ]);
    }


    /**
     * Отправить сообщение в ВК
     * @param string $externalId - ID пользователя ВК (user_id)
     * @param string $content - Текст сообщения
     * @param array $attachments - Вложения
     * @return bool
     */
    public function sendMessage(string $externalId, string $content, array $attachments = []): bool
    {
        try {

            $response = Http::asForm()->post('https://api.vk.com/method/messages.send', [
                'peer_id' => $externalId,
                'message' => $content,
                'random_id' => random_int(1, 999999999),
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
            ]);


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
                    'user_id' => $externalId,
                    'error' => $error,
                    'error_code' => $data['error']['error_code'] ?? null
                ]);
                return false;
            }


            return true;

        } catch (\Exception $e) {
            Log::error("VKAdapter: Exception while sending message", [
                'user_id' => $externalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Отправить вложение в ВК
     */
    protected function sendAttachment(string $externalId, array $attachment): bool
    {
        try {
            $attachmentString = '';

            switch ($attachment['type'] ?? null) {
                case 'photo':
                    // Формат для ВК: photo123456_789
                    $attachmentString = $attachment['attachment_id'] ?? $attachment['url'];
                    break;
                case 'document':
                    $attachmentString = $attachment['attachment_id'] ?? $attachment['url'];
                    break;
                case 'audio':
                    $attachmentString = $attachment['attachment_id'] ?? $attachment['url'];
                    break;
                default:
                    Log::warning("VKAdapter: Unsupported attachment type", [
                        'type' => $attachment['type'] ?? 'unknown'
                    ]);
                    return false;
            }

            if (!$attachmentString) {
                return false;
            }

            $response = Http::post('https://api.vk.com/method/messages.send', [
                'user_id' => $externalId,
                'attachment' => $attachmentString,
                'access_token' => $this->settings->access_token,
                'v' => $this->settings->api_version,
                'random_id' => random_int(1, 999999999),
            ]);

            return $response->successful() && !isset($response['error']);

        } catch (\Exception $e) {
            Log::error("VKAdapter: Failed to send attachment", [
                'user_id' => $externalId,
                'error' => $e->getMessage()
            ]);
            return false;
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
                'user_id' => $externalId,
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
