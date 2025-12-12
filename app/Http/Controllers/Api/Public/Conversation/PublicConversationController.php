<?php

namespace App\Http\Controllers\Api\Public\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\File\FileStorageService;
use App\Services\Messaging\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;

class PublicConversationController extends Controller
{
    protected $conversationService;
    protected $fileStorage;

    public function __construct(
        ConversationService $conversationService,
        FileStorageService  $fileStorage
    )
    {
        $this->conversationService = $conversationService;
        $this->fileStorage = $fileStorage;
    }

    /**
     * Получить или создать conversation для клиента
     * GET /api/public/conversations/client?source=web_chat&external_id=...
     */
    public function getOrCreateForClient(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'source' => 'nullable|in:telegram,whatsapp,web_chat,email',
            'external_id' => 'nullable|string',
        ]);

        $source = $data['source'] ?? 'web_chat';
        $externalId = $data['external_id'] ?? '';

        // Ищем по external_id и source
        $conversation = Conversation::where('source', $source)
            ->where('external_id', $externalId)
            ->when($externalId !== '', function ($query) {
                return $query;
            }, function ($query) use ($data) {
                return $query->where('client_id', $data['client_id']);
            })
            ->first();

        if ($conversation) {
            // Обновляем client_id если его не было
            if ($data['client_id'] ?? null && !$conversation->client_id) {
                $conversation->update(['client_id' => $data['client_id']]);
            }
        } else {
            // Создаём новый
            $conversation = Conversation::create([
                'client_id' => $data['client_id'] ?? null,
                'source' => $source,
                'external_id' => $externalId,
                'status' => 'active',
                'last_message_at' => now(),
                'unread_messages_count' => 0,
            ]);
        }

        // Загружаем сообщения и связи
        $conversation->load([
            'messages.attachments',
            'client.profile',
            'assignedUser',
        ]);

        return response()->json([
            'success' => true,
            'data' => $conversation
        ], 200);
    }

    /**
     * Отправить сообщение клиентом
     * POST /api/public/conversations/{conversation}/reply
     */
    public function reply(SendMessageRequest $request, Conversation $conversation)
    {

        $validated = $request;

        $attachmentsData = [];

        try {
            // Обрабатываем файлы если они есть
            if ($request->hasFile('attachments')) {
                $attachmentsData = $this->fileStorage->storeAttachments(
                    $request->file('attachments')
                );
            }

            // Добавляем входящее сообщение (от клиента)
            $message = $this->conversationService->addMessage($conversation, [
                'direction' => Message::DIRECTION_INCOMING,
                'content' => $validated['content'],
                'attachments' => $attachmentsData,
                'status' => Message::STATUS_SENT,
            ]);

            // Обновляем время последнего сообщения и увеличиваем счётчик непрочитанных
            $conversation->update([
                'last_message_at' => now(),
                'unread_messages_count' => $conversation->messages()
                    ->where('direction', Message::DIRECTION_INCOMING)
                    ->where('status', '!=', Message::STATUS_READ)
                    ->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data' => $message,
            ], 201);

        } catch (\Exception $e) {
            // При ошибке удаляем загруженные файлы
            if (!empty($attachmentsData)) {
                foreach ($attachmentsData as $attachment) {
                    $this->fileStorage->delete($attachment['file_path']);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отметить conversation как прочитанный
     * POST /api/public/conversations/{conversation}/read
     */
    public function read(Conversation $conversation)
    {
        // Обновляем статусы исходящих сообщений (от оператора) на прочитанные
        $conversation->messages()
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->whereIn('status', [
                Message::STATUS_SENT,
                Message::STATUS_DELIVERED,
            ])
            ->update(['status' => Message::STATUS_READ]);

        // Сбрасываем счётчик непрочитанных входящих сообщений
        $conversation->update(['unread_messages_count' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read.'
        ], 200);
    }
}
