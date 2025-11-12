<?php

namespace App\Services\Email;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MailSetting;
use App\Services\Messaging\ConversationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class EmailService
{
    protected ConversationService $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    /**
     * Обработать входящее письмо от Yandex 360 webhook
     */
    public function handleIncomingEmail(array $emailData): array
    {
        try {
            // Валидируем данные
            if (!isset($emailData['from']) || !isset($emailData['subject']) || !isset($emailData['text'])) {
                Log::warning("EmailService: Missing required fields", ['data' => $emailData]);
                return ['ok' => false, 'error' => 'Missing required fields'];
            }

            return DB::transaction(function () use ($emailData) {
                // Извлекаем данные письма
                $fromEmail = $emailData['from'];
                $subject = $emailData['subject'] ?? 'No subject';
                $text = $emailData['text'];
                $text = $this->extractTextFromHtml($text);

                $messageId = $emailData['message_id'] ?? uniqid('email_');
                $attachments = $emailData['attachments'] ?? [];

                Log::info("EmailService: Processing incoming email", [
                    'from' => $fromEmail,
                    'subject' => $subject,
                    'attachments' => $attachments
                ]);

                // Ищем или создаём клиента по email
                $client = $this->findClient($fromEmail);


                // Ищем или создаём conversation
                $conversation = Conversation::firstOrCreate(
                    [
                        'source' => 'email',
                        'external_id' => $fromEmail,
                    ],
                    [
                        'client_id' => $client?->id ?? null,
                        'status' => 'active',
                        'last_message_at' => now(),
                        'unread_messages_count' => 0,
                    ]
                );


                // Добавляем входящее сообщение
                $messageData = [
                    'direction' => 'incoming',
                    'content' => $text,
                    'content_type' => 'text',
                    'status' => 'delivered',
                    'source_data' => [
                        'email_from' => $fromEmail,
                        'email_subject' => $subject,
                        'email_message_id' => $messageId,
                        'email_attachments' => $attachments,
                    ]
                ];

                // Обработка вложений если есть
                if (!empty($attachments)) {
                    $processedAttachments = $this->processAttachments($attachments);
                    $messageData['attachments'] = $processedAttachments;
                }

                $this->conversationService->addMessage($conversation, $messageData);


                return ['ok' => true, 'conversation_id' => $conversation->id];

            });

        } catch (Exception $e) {
            Log::error("EmailService: Exception in handleIncomingEmail", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }


    /**
     * Извлечь текст из HTML письма
     */
    protected function extractTextFromHtml(string $html): string
    {
        // Удаляем HTML теги
        $text = strip_tags($html);

        // Удаляем лишние пробелы и переносы строк
        $text = preg_replace('/\s+/', ' ', $text);

        // Удаляем "Sent from", "Wednesday" и прочие служебные данные
        $text = preg_replace('/Sent from.*$/is', '', $text);
        $text = preg_replace('/Wednesday.*$/is', '', $text);

        // Trim
        $text = trim($text);

        return $text;
    }

    /**
     * Найти или создать клиента по email
     */
    protected function findClient(string $email): ?Client
    {
        try {
            // Ищем клиента по email
            $client = Client::where('email', $email)->first();

            if ($client) {
                return $client;
            } else {
                return null;
            }

        } catch (Exception $e) {
            Log::error("EmailService: Failed to create client", [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Обработать вложения из письма
     */
    protected function processAttachments(array $attachments): array
    {
        $processed = [];

        foreach ($attachments as $attachment) {
            // Сохраняем только ссылку на вложение
            $processed[] = [
                'type' => 'file',
                'url' => $attachment['url'] ?? null,
                'file_name' => $attachment['filename'] ?? 'attachment',
                'attachment_id' => $attachment['id'] ?? uniqid('att_')
            ];
        }

        return $processed;
    }
}
