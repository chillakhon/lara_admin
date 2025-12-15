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

    public function handleIncomingEmail(array $emailData): array
    {
        try {
            if (!isset($emailData['from']) || !isset($emailData['subject']) || !isset($emailData['text'])) {
                Log::warning("EmailService: Missing required fields", ['data' => $emailData]);
                return ['ok' => false, 'error' => 'Missing required fields'];
            }

            return DB::transaction(function () use ($emailData) {
                $fromEmail = $emailData['from'];
                $subject = $emailData['subject'] ?? 'No subject';
                $text = $emailData['text'];
                $text = $this->extractTextFromHtml($text);

                $messageId = $emailData['message_id'] ?? uniqid('email_');
                $attachments = $emailData['attachments'] ?? [];

                Log::info("EmailService: Processing incoming email", [
                    'from' => $fromEmail,
                    'subject' => $subject,
                    'attachments_count' => count($attachments)
                ]);

                $client = $this->findClient($fromEmail);

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

                $messageData = [
                    'direction' => 'incoming',
                    'content' => $text,
                    'content_type' => 'text',
                    'status' => 'delivered',
                    'source_data' => [
                        'email_from' => $fromEmail,
                        'email_subject' => $subject,
                        'email_message_id' => $messageId,
                    ]
                ];

                // ← ОБНОВИЛИ: просто передаем attachments как есть
                if (!empty($attachments)) {
                    $messageData['attachments'] = $attachments;

                    Log::info("EmailService: Attachments to save", [
                        'count' => count($attachments),
                        'attachments' => $attachments
                    ]);
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

    protected function extractTextFromHtml(string $html): string
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/Sent from.*$/is', '', $text);
        $text = preg_replace('/Wednesday.*$/is', '', $text);
        $text = trim($text);

        return $text;
    }

    protected function findClient(string $email): ?Client
    {
        try {
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
}
