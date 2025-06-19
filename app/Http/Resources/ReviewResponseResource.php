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
            'user' => $this->formatUser($this->user),
            'client' => $this->formatUser($this->client),
            'responded_to' => $this->responded_to ? $this->formatRespondedTo($this->responded_to) : null,
            'created_at' => $this->created_at,
        ];
    }

    protected function formatUser($user): ?array
    {
        if (!$user)
            return null;

        return [
            'id' => $user->id,
            'name' => method_exists($user, 'get_full_name') ? $user->get_full_name() : null,
            'email' => $user->email ?? null,
        ];
    }

    protected function formatRespondedTo($response): array
    {
        return [
            'id' => $response->id,
            'content' => $response->content,
            'user' => $this->formatUser($response->user),
            'client' => $this->formatUser($response->client),
            'created_at' => $response->created_at,
        ];
    }
}