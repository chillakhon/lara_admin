<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'rating' => $this->rating,
            'is_verified' => $this->is_verified,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->format('d.m.Y H:i'),
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'client' => [
                'id' => $this->client->id,
                'name' => $this->client->full_name,
                'avatar' => $this->client->avatar_url,
            ],
            'reviewable' => [
                'id' => $this->reviewable->id,
                'type' => class_basename($this->reviewable_type),
                'name' => $this->reviewable->name,
            ],
            'attributes' => ReviewAttributeResource::collection($this->whenLoaded('attributes')),
            'responses' => ReviewResponseResource::collection($this->whenLoaded('responses')),
            'images' => $this->whenLoaded('images', function() {
                return $this->images->map(fn($image) => [
                    'id' => $image->id,
                    'url' => $image->url,
                    'thumbnail' => $image->thumbnail_url,
                ]);
            }),
        ];
    }
} 