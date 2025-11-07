<?php

namespace App\Services\Telegram;

use App\Models\UserProfile;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Messaging\ConversationService;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TelegramService
{
    private ConversationService $conversationService;
    private TelegramCommandHandler $commandHandler;

    public function __construct(
        ConversationService    $conversationService,
        TelegramCommandHandler $commandHandler,
    )
    {
        $this->conversationService = $conversationService;
        $this->commandHandler = $commandHandler;
    }


    public function findOrCreateConversationAndSendMessage(
        int              $telegramUserId,
        UserProfile|null $client_profile,
        string           $content,
    )
    {

        $conversation = null;

        if ($client_profile) {
            $conversation = Conversation::where('client_id', $client_profile->client_id)
                ->where('source', 'telegram')
                ->first();
        }

        if (!$conversation) {
            $conversation = Conversation::where('external_id', $telegramUserId)
                ->where('source', 'telegram')
                ->first();
        }


        if (!$conversation) {
            $conversation = $this->conversationService->createConversation('telegram', $telegramUserId, $client_profile->client_id ?? null);
        }


        $messageData = [
            'conversation_id' => $conversation->id,
            'content' => $content,
            'direction' => 'incoming',
            'status' => 'sending',
            'content_type' => 'text',
            'source_data' => null
        ];

        $this->conversationService->addMessage($conversation, $messageData);

    }

    /**
     * Синхронизация профиля пользователя из Telegram
     */
    public function syncUserProfile(
        int   $telegramUserId,
        array $userInfo,
        int   $chatId
    ): UserProfile
    {
        $userProfile = UserProfile::where('telegram_user_id', $telegramUserId)->first();

        if (!$userProfile) {
            throw new \Exception('User profile not found');
        }

        // Обновляем данные
        $userProfile->update([
            'first_name' => $userInfo['first_name'] ?? null,
            'last_name' => $userInfo['last_name'] ?? null,
            'telegram_chat_id' => $chatId, // Сохраняем chat_id для отправки сообщений
        ]);

        return $userProfile;
    }
}
