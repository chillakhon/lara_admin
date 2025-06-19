<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $isAdmin = $request->get('admin', false);

        return [
            'id' => $this->id,
            'content' => $this->content,
            'rating' => $this->rating,
            'is_verified' => $this->is_verified,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->format('d.m.Y H:i'),
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'status' => $this->status, // Добавляем статус
            'client' => $this->when($this->client, function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->full_name,
                    'email' => $this->client->email, // Добавляем email клиента
                    'avatar' => $this->client->avatar_url,
                ];
            }, null),
            $this->mergeWhen($isAdmin, [
                'reviewable' => $this->when($this->reviewable, function () {
                    return [
                        'id' => $this->reviewable->id,
                        'type' => class_basename($this->reviewable_type),
                        'name' => $this->reviewable->name,
                        'slug' => $this->reviewable?->slug ?? null,
                    ];
                }, null),
            ]),
            'attributes' => ReviewAttributeResource::collection($this->whenLoaded('attributes')),
            'responses' => ReviewResponseResource::collection($this->whenLoaded('responses')),
            // images are not necessary for now
            // 'images' => $this->whenLoaded('images', function () {
            //     return $this->images->map(fn($image) => [
            //         'id' => $image->id,
            //         'url' => $image->url,
            //         'thumbnail' => $image->thumbnail_url,
            //     ]);
            // }),
        ];
    }
}
