<?php

namespace App\Http\Resources\Conversation;

use App\Http\Resources\Client\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'external_id' => $this->external_id,
            'status' => $this->status,
            'unread_messages_count' => $this->unread_messages_count,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'client' => new ClientResource($this->whenLoaded('client')),
            'assigned_user' => $this->whenLoaded('assignedUser'),
            'messages' => $this->whenLoaded('messages'),
            'participants' => $this->whenLoaded('participants'),
        ];
    }
}
