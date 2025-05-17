<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'blur_hash' => $this->blur_hash,
            'name' => $this->path,
            'order' => $this->order,
            'is_main' => $this->is_main ?? false,
        ];
    }
}
