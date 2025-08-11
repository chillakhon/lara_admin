<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SlideResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'text' => $this->text,
            'order' => $this->order,
            'is_active' => (bool)$this->is_active,
//            'image_path' => $this->image_path,
            'image_paths' => $this->image_paths ? json_decode($this->image_paths, true) : null,
//            'image_url' => $this->image_url,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
