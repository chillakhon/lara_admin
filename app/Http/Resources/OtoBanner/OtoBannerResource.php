<?php

namespace App\Http\Resources\OtoBanner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtoBannerResource extends JsonResource
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

            'button_enabled' => $this->button_enabled,
            'button_text' => $this->button_text,

            'input_field_enabled' => $this->input_field_enabled,
            'input_field_type' => $this->input_field_type->value,
            'input_field_type_label' => $this->input_field_type->label(),
            'input_field_label' => $this->input_field_label,
            'input_field_placeholder' => $this->input_field_placeholder,
            'input_field_required' => $this->input_field_required,

            'display_delay_seconds' => $this->display_delay_seconds,

            'privacy_text' => $this->privacy_text,

            'segment_ids' => $this->segment_ids,

            'image' => $this->when($this->relationLoaded('mainImage') && $this->mainImage, [
                'id' => $this->mainImage?->id,
                'url' => $this->mainImage?->url,
                'path' => $this->mainImage?->path,
            ]),

            'views_count' => $this->when(isset($this->views_count), $this->views_count),
            'submissions_count' => $this->when(isset($this->submissions_count), $this->submissions_count),
            'conversion_rate' => $this->when(isset($this->conversion_rate), $this->conversion_rate),

            'is_active' => $this->isActive(),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
