<?php

namespace App\Http\Resources\OtoBanner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtoBannerListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'device_type' => $this->device_type->value,
            'device_type_label' => $this->device_type->label(),

            'title' => $this->title,
            'subtitle' => $this->subtitle,

            'button_text' => $this->button_text,
            'input_field_label' => $this->input_field_label,
            'input_field_type' => $this->input_field_type,

            'display_delay_seconds' => $this->display_delay_seconds,

            'image' => $this->when($this->relationLoaded('mainImage') && $this->mainImage,
                [
                    'url' => $this->mainImage?->url,
                ]
            ),

            'views_count' => $this->views_count ?? 0,
            'submissions_count' => $this->submissions_count ?? 0,
            'conversion_rate' => round($this->conversion_rate ?? 0, 2),

            'created_at' => $this->created_at?->format('d.m.Y H:i'),
        ];
    }
}
