<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'depth' => $this->depth,
            'parent_id' => $this->parent_id,

            'show_in_catalog_menu' => $this->show_in_catalog_menu,
            'show_as_home_banner' => $this->show_as_home_banner,
            'menu_order' => $this->menu_order,
            'banner_image' => $this->banner_image,
            'is_new_product' => $this->is_new_product,
            'banner_url' => $this->banner_image ? url('storage/' . $this->banner_image) : null,

            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
