<?php

namespace App\Http\Resources\OtoBanner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtoBannerSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'banner' => $this->when($this->relationLoaded('otoBanner'), [
                'id' => $this->otoBanner?->id,
                'name' => $this->otoBanner?->name,
            ]),

            'client' => $this->when($this->relationLoaded('client'), [
                'id' => $this->client?->id,
                'name' => $this->client?->get_full_name(),
                'email' => $this->client?->email,
                'phone' => $this->client?->profile?->phone,
            ]),

            'manager' => $this->when($this->relationLoaded('manager'), [
                'id' => $this->manager?->id,
                'name' => $this->manager?->get_full_name(),
            ]),

            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,

            'source' => $this->source,
            'status' => $this->status,

            'meta' => $this->meta,

            'ip' => $this->ip,
            'user_agent' => $this->user_agent,

            'read_at' => $this->read_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
