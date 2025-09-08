<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'blur_hash' => $this->blur_hash,
            'path' => $this->path,
//            'full_url' => $this->path ? url(Storage::url($this->path)) : null,
            'order' => $this->order,
            'is_main' => $this->is_main ?? false,
        ];
    }
}
