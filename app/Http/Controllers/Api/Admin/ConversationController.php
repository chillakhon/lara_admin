<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\File\FileStorageService;
use App\Services\Messaging\ConversationService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    protected $conversationService;
    protected $fileStorage;

    public function __construct(
        ConversationService $conversationService,
        FileStorageService $fileStorage
    ) {
        $this->conversationService = $conversationService;
        $this->fileStorage = $fileStorage;
    }

    // Список чатов (пагинация)
    public function index(Request $request)
    {
        // Опциональный параметр фильтрации
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1',
            'source' => 'nullable|in:telegram,whatsapp,web_chat,vk,email',
        ]);

        // Базовый запрос с нужными связями
        $query = Conversation::with(['lastMessage', 'client.profile', 'assignedUser'])
            ->whereHas('messages')
            ->orderBy('last_message_at', 'desc');

        // Если пришёл source — добавляем where-условие
        if (!empty($validated['source'])) {
            $query->where('source', $validated['source']);
        }

        // Пагинация
        $perPage = $validated['per_page'] ?? 20;
        $conversations = $query->paginate($perPage);

        return response()->json([
            'data' => $conversations
        ]);
    }

    // Получение конкретного чата + сообщений и связей
    public function show(Conversation $conversation)
    {
        $conversation->load([
            'messages.attachments',
            'client.profile',
            'assignedUser',
            'participants.user'
        ]);

        // Отмечаем как прочитанные только сообщения от клиента
        $this->conversationService->markAsRead($conversation);

        return response()->json([
            'data' => $conversation
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'source' => 'nullable|string',
            'external_id' => 'nullable|string',
            'content' => 'required|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,mp3,wav,ogg,m4a|max:10240',
        ]);

        $attachmentsData = [];

        try {
            // Обрабатываем файлы если они есть
            if ($request->hasFile('attachments')) {
                $attachmentsData = $this->fileStorage->storeAttachments(
                    $request->file('attachments')
                );
            }

            // 1) создаём сам разговор
            $conversation = $this->conversationService->createConversation(
                $validated['source'] ?? 'web_chat',
                $validated['external_id'] ?? '',
                $validated['client_id']
            );

            // 2) сразу добавляем первое сообщение
            $message = $this->conversationService->addMessage($conversation, [
                'direction' => 'incoming',
                'content' => $validated['content'],
                'attachments' => $attachmentsData,
            ]);

            // 3) вернуть разговор с вложениями сообщений
            $conversation->load('messages.attachments');

            return response()->json([
                'message' => 'Conversation started successfully.',
                'conversation' => $conversation,
                'firstMessage' => $message,
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
                'message' => 'Failed to create conversation: ' . $e->getMessage()
            ], 500);
        }
    }



    // Ответить на сообщение
    public function reply(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,mp3,wav,ogg,m4a|max:10240',
        ]);

        $attachmentsData = [];

        try {
            // Обрабатываем файлы если они есть
            if ($request->hasFile('attachments')) {
                $attachmentsData = $this->fileStorage->storeAttachments(
                    $request->file('attachments')
                );
            }

            // 1) Добавляем исходящее сообщение
            $message = $this->conversationService->addMessage($conversation, [
                'direction' => Message::DIRECTION_OUTGOING,
                'content' => $validated['content'],
                'attachments' => $attachmentsData,
                'status' => Message::STATUS_SENT,
            ]);

            // 2) Обновляем время последнего сообщения
            $conversation->update([
                'last_message_at' => now(),
                // 3) Сбрасываем счётчик непрочитанных входящих
                'unread_messages_count' => 0,
            ]);

            return response()->json([
                'message' => 'Reply sent successfully.',
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
                'message' => 'Failed to send reply: ' . $e->getMessage()
            ], 500);
        }
    }

    public function incomingForClient(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'external_id' => 'nullable|string',
            'source' => 'nullable|in:telegram,whatsapp,web_chat,email,vk',
            'content' => 'required|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,mp3,wav,ogg,m4a|max:10240',
        ]);

        // Ищем разговор
        $conversation = Conversation::query()
            ->when($validated['client_id'] ?? null, function ($q, $clientId) {
                $q->where('client_id', $clientId);
            })
            ->when($validated['external_id'] ?? null, function ($q, $externalId) use ($validated) {
                $q->where('external_id', $externalId)
                    ->when($validated['source'] ?? null, fn($qq) => $qq->where('source', $validated['source']));
            })
            ->first();

        if (!$conversation) {
            return response()->json([
                'error' => 'Диалог не найден'
            ], 404);
        }

        $attachmentsData = [];

        try {
            // Обрабатываем файлы если они есть
            if ($request->hasFile('attachments')) {
                $attachmentsData = $this->fileStorage->storeAttachments(
                    $request->file('attachments')
                );
            }

            // Добавляем сообщение через сервис
            $message = $this->conversationService->addMessage($conversation, [
                'direction' => Message::DIRECTION_INCOMING,
                'content' => $validated['content'],
                'attachments' => $attachmentsData,
                'status' => Message::STATUS_SENT,
            ]);

            return response()->json([
                'message' => 'Входящее сообщение сохранено.',
                'data' => $message,
                'unread_count' => $conversation->unread_messages_count,
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
                'message' => 'Failed to save message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function incoming(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,mp3,wav,ogg,m4a|max:10240',
        ]);

        $attachmentsData = [];

        try {
            // Обрабатываем файлы если они есть
            if ($request->hasFile('attachments')) {
                $attachmentsData = $this->fileStorage->storeAttachments(
                    $request->file('attachments')
                );
            }

            $message = $this->conversationService->addMessage($conversation, [
                'direction' => Message::DIRECTION_INCOMING,
                'content' => $validated['content'],
                'attachments' => $attachmentsData,
                'status' => Message::STATUS_SENT,
            ]);

            return response()->json([
                'message' => 'Incoming message saved.',
                'data' => $message,
                'unread_count' => $conversation->unread_messages_count,
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
                'message' => 'Failed to save incoming message: ' . $e->getMessage()
            ], 500);
        }
    }

    // Закрыть чат
    public function close(Conversation $conversation)
    {
        $this->conversationService->closeConversation($conversation);

        return response()->json([
            'message' => 'Conversation closed.'
        ], 200);
    }

    // Назначить оператора (менеджера)
    public function assign(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($validated['user_id']);

        $this->conversationService->assignManager($conversation, $user);

        return response()->json([
            'message' => 'Conversation assigned to user.',
            'assigned_user' => $user
        ], 200);
    }
}
