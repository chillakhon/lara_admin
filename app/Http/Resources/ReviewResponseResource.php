<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user?->get_full_name() ?? null,
                'email' => $this->user->email,
            ] : null,
            'client' => $this->client ? [
                "id" => $this->client->id,
                "name" =>  $this->client?->get_full_name() ?? null,
                "email" => $this->client?->email ?? null,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}