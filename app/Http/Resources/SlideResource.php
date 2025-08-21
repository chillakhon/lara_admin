<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SlideResource extends JsonResource
{
    public function toArray($request)
    {
        $imagePaths = $this->image_paths ? json_decode($this->image_paths, true) : null;

        $imageUrls = null;
        if ($imagePaths) {
            $imageUrls = collect($imagePaths)->mapWithKeys(function ($path, $key) {
                return [$key => asset('storage/' . $path)];
            });
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'text' => $this->text,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
//            'image_paths' => $imagePaths,
            'image_urls'  => $imageUrls,      // готовые полные URL
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

}
