<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SlideResource extends JsonResource
{
    public function toArray($request)
    {
        // Desktop изображения
        $imagePaths = $this->image_paths ? json_decode($this->image_paths, true) : null;
        $imageUrls = null;
        if ($imagePaths) {
            $imageUrls = collect($imagePaths)->mapWithKeys(function ($path, $key) {
                return [$key => asset('storage/' . $path)];
            });
        }

        // Mobile изображения
        $mobileImagePaths = $this->image_mobile_paths ? json_decode($this->image_mobile_paths, true) : null;
        $mobileImageUrls = null;
        if ($mobileImagePaths) {
            $mobileImageUrls = collect($mobileImagePaths)->mapWithKeys(function ($path, $key) {
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
            'image_urls' => $imageUrls,           // Desktop изображения
            'mobile_image_urls' => $mobileImageUrls, // Mobile изображения
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
