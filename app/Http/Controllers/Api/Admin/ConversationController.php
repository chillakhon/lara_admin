<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\ConversationService;
use Illuminate\Http\Request;
use Psy\Readline\Hoa\Event;

class ConversationController extends Controller
{
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
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
            'attachments' => 'nullable|array',
        ]);

        // 1) создаём сам разговор
        $conversation = $this->conversationService->createConversation(
            $validated['source'] ?? 'web_chat',
            $validated['external_id'] ?? '',
            $validated['client_id']
        );


//        return $conversation;

        // 2) сразу добавляем первое сообщение
        $message = $this->conversationService->addMessage($conversation, [
            'direction' => 'incoming',
            'content' => $validated['content'],
            'attachments' => $validated['attachments'] ?? [],
        ]);

        // 3) вернуть разговор с вложениями сообщений
        $conversation->load('messages.attachments');

        return response()->json([
            'message' => 'Conversation started successfully.',
            'conversation' => $conversation,
            'firstMessage' => $message,
        ], 201);
    }


    public function showForClient(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'external_id' => 'nullable|string',
            'source' => 'nullable|in:telegram,whatsapp,web_chat,email,vk',
        ]);

        // Ищем разговор
        $conversation = Conversation::query()
            ->when($data['client_id'] ?? null, function ($q, $clientId) {
                $q->where('client_id', $clientId);
            })
            ->when($data['external_id'] ?? null, function ($q, $externalId) use ($data) {
                $q->where('external_id', $externalId)
                    ->when($data['source'] ?? null, fn($qq) => $qq->where('source', $data['source']));
            })
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Диалог не найден'
            ], 404);
        }

        // Загружаем нужные связи
        $conversation->load([
            'messages.attachments',
            'client.profile',
        ]);

        // Обновляем статусы исходящих сообщений (как прочитанные)
        $conversation->messages()
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->whereIn('status', [
                Message::STATUS_SENT,
                Message::STATUS_DELIVERED,
            ])
            ->update(['status' => Message::STATUS_READ]);

        return response()->json([
            'success' => true,
            'data' => $conversation
        ]);
    }


    // Ответить на сообщение
    public function reply(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'attachments' => 'nullable|array',
        ]);

        // 1) Добавляем исходящее сообщение
        $message = $this->conversationService->addMessage($conversation, [
            'direction' => Message::DIRECTION_OUTGOING,
            'content' => $validated['content'],
            'attachments' => $validated['attachments'] ?? [],
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
    }


    public function incomingForClient(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'external_id' => 'nullable|string',
            'source' => 'nullable|in:telegram,whatsapp,web_chat,email,vk',
            'content' => 'required|string',
            'attachments' => 'nullable|array',
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

        // Добавляем сообщение через сервис
        $message = $this->conversationService->addMessage($conversation, [
            'direction' => Message::DIRECTION_INCOMING,
            'content' => $validated['content'],
            'attachments' => $validated['attachments'] ?? [],
            'status' => Message::STATUS_SENT,
        ]);


        return response()->json([
            'message' => 'Входящее сообщение сохранено.',
            'data' => $message,
            'unread_count' => $conversation->unread_messages_count,
        ], 201);
    }


    public function incoming(Request $request, Conversation $conversation)
    {


        $validated = $request->validate([
            'content' => 'required|string',
            'attachments' => 'nullable|array',
        ]);

        $message = $this->conversationService->addMessage($conversation, [
            'direction' => Message::DIRECTION_INCOMING,
            'content' => $validated['content'],
            'attachments' => $validated['attachments'] ?? [],
            'status' => Message::STATUS_SENT,
        ]);


        return response()->json([
            'message' => 'Incoming message saved.',
            'data' => $message,
            'unread_count' => $conversation->unread_messages_count,
        ], 201);
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
