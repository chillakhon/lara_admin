<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'has_variants' => $this->has_variants,
            'allow_preorder' => $this->allow_preorder,
            'default_unit' => $this->whenLoaded('defaultUnit', function() {
                return [
                    'id' => $this->defaultUnit->id,
                    'name' => $this->defaultUnit->name,
                ];
            }),
            'categories' => $this->whenLoaded('categories', function() {
                return $this->categories->map(function($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                    ];
                });
            }),
            'main_image' => $this->whenLoaded('images', function() {
                $mainImage = $this->images
                    ->sortBy('order')
                    ->firstWhere('is_main', true) ??
                    $this->images->sortBy('order')->first();

                return $mainImage ? [
                    'id' => $mainImage->id,
                    'url' => $mainImage->url,
                ] : null;
            }),
            'price_range' => $this->whenLoaded('activeVariants', function() {
                $prices = $this->activeVariants->pluck('price');
                if ($prices->isEmpty()) {
                    return null;
                }
                return [
                    'min' => $prices->min(),
                    'max' => $prices->max(),
                ];
            }),
            'variants' => $this->whenLoaded('activeVariants', function() {
                return $this->activeVariants->map(function($variant) {
                    return [
                        'id' => $variant->id,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'unit' => $variant->unit->name ?? null,
                        'option_values' => $variant->optionValues->map(function($optionValue) {
                            return [
                                'id' => $optionValue->id,
                                'name' => $optionValue->name,
                                'color_code' => $optionValue->color_code,
                                'option' => [
                                    'id' => $optionValue->option->id,
                                    'name' => $optionValue->option->name,
                                ],
                            ];
                        }),
                        'images' => $variant->images->map(function($image) {
                            return [
                                'id' => $image->id,
                                'url' => $image->url,
                            ];
                        }),
                    ];
                });
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}


