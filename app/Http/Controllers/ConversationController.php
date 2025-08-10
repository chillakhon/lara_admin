<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\Messaging\ConversationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    public function index()
    {
        $conversations = Conversation::with(['lastMessage', 'client', 'assignedUser'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

//        return Inertia::render('Dashboard/Conversations/Index', [
//            'conversations' => $conversations
//        ]);
    }

    public function show(Conversation $conversation)
    {
        $conversation->load([
            'messages.attachments',
            'client',
            'assignedUser',
            'participants.user'
        ]);

        $this->conversationService->markAsRead($conversation);

        return response()->json([
            'conversation' => $conversation
        ]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'attachments' => 'array'
        ]);

        $message = $this->conversationService->addMessage($conversation, [
            'direction' => 'outgoing',
            'content' => $validated['content'],
            'attachments' => $validated['attachments'] ?? []
        ]);

        return back();
    }

    public function close(Conversation $conversation)
    {
        $this->conversationService->closeConversation($conversation);
        return back();
    }

    public function assign(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $this->conversationService->assignManager(
            $conversation,
            \App\Models\User::find($validated['user_id'])
        );

        return back();
    }
}
