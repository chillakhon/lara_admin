<?php

namespace App\Http\Controllers\Api\Public\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Client;
use App\Services\Messaging\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WhatsAppWebhookController extends Controller
{
    protected ConversationService $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    /**
     * Получить webhook от WhatsApp сервиса
     * POST /api/public/whatsapp/webhook
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->validate([
                'phone_number' => 'required|string',
                'message_text' => 'required|string',
                'message_id' => 'required|string',
                'timestamp' => 'required|string',
                'from_id' => 'required|string',
            ]);

            Log::info("WhatsAppWebhookController: Webhook received", ['data' => $data]);

            return DB::transaction(function () use ($data) {
                // external_id = phone_number
                $phoneNumber = $data['phone_number'];
                $messageText = $data['message_text'];

                // Ищем или создаём conversation
                $conversation = Conversation::firstOrCreate(
                    [
                        'source' => 'whatsapp',
                        'external_id' => $phoneNumber,
                    ],
                    [
                        'status' => 'active',
                        'last_message_at' => now(),
                        'unread_messages_count' => 0,
                    ]
                );

                // Добавляем входящее сообщение
                $messageData = [
                    'direction' => 'incoming',
                    'content' => $messageText,
                    'content_type' => 'text',
                    'status' => 'delivered',
                    'source_data' => [
                        'whatsapp_message_id' => $data['message_id'],
                        'whatsapp_phone_number' => $phoneNumber,
                        'whatsapp_from_id' => $data['from_id'],
                        'timestamp' => $data['timestamp'],
                    ]
                ];

                $this->conversationService->addMessage($conversation, $messageData);

                Log::info("WhatsAppWebhookController: Message saved", [
                    'conversation_id' => $conversation->id,
                    'message_id' => $data['message_id']
                ]);

                event(new \App\Events\ConversationUpdated($conversation));

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook processed successfully'
                ], 200);

            });

        } catch (\Exception $e) {
            Log::error("WhatsAppWebhookController: Exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
