<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

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
        return [
            // Приватный канал для админов
            new PrivateChannel('conversation.' . $this->message->conversation_id),

            new Channel('public.conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        // Ограничиваем content чтобы не превышать лимит Reverb (10KB)
        $content = $this->message->content;
        if (mb_strlen($content) > 2000) {
            $content = mb_substr($content, 0, 2000) . '...';
        }

        // Для вложений отправляем только метаданные (без url/file_path — они могут быть длинными)
        $attachments = $this->message->attachments->map(function ($att) {
            return [
                'id' => $att->id,
                'type' => $att->type,
                'file_name' => $att->file_name,
                'mime_type' => $att->mime_type,
                'url' => $att->url,
            ];
        })->toArray();

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'direction' => $this->message->direction,
            'content' => $content,
            'status' => $this->message->status,
            'created_at' => $this->message->created_at->toDateTimeString(),
            'attachments' => $attachments,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageCreated';
    }
}
