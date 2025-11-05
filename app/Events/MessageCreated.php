<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageCreated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load('attachments');
    }

    public function broadcastOn(): array
    {
        Log::info('Public conversation channel auth', [
            $this->message,
        ]);
        return [
            // Приватный канал для админов
            new PrivateChannel('conversation.' . $this->message->conversation_id),

            new Channel('public.conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'direction' => $this->message->direction,
            'content' => $this->message->content,
            'status' => $this->message->status,
            'created_at' => $this->message->created_at->toDateTimeString(),
            'attachments' => $this->message->attachments->toArray(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageCreated';
    }
}
