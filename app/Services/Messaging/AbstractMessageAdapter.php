<?php

namespace App\Services\Messaging;

use App\Contracts\MessageSourceAdapter;
use App\Models\Conversation;
use App\Models\Message;

abstract class AbstractMessageAdapter implements MessageSourceAdapter
{
    protected function createMessage(Conversation $conversation, array $data): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outgoing',
            'content' => $data['content'],
            'content_type' => $data['content_type'] ?? 'text',
            'status' => 'sent',
            'source_data' => $data['source_data'] ?? null,
        ]);
    }

    protected function handleAttachments(Message $message, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $message->attachments()->create([
                'type' => $attachment['type'],
                'url' => $attachment['url'],
                'file_name' => $attachment['file_name'] ?? null,
                'file_size' => $attachment['file_size'] ?? null,
                'mime_type' => $attachment['mime_type'] ?? null,
            ]);
        }
    }
} 