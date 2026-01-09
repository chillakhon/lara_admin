<?php

namespace App\Http\Resources\UserClient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'birthday' => $this->birthday,
            'telegram_user_id' => $this->telegram_user_id,
            'telegram_chat_id' => $this ->telegram_chat_id,
            'image' => $this?->image,
        ];
    }
}
