<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
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
            'absorbency_level' => $this->has_variants,
            'allow_preorder' => $this->allow_preorder,
            'after_purchase_processing_time' => $this->after_purchase_processing_time,
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
            'images' => $this->whenLoaded('images', function() {
                return $this->images
                    ->sortBy('order')
                    ->values()
                    ->map(function($image) {
                        return [
                            'id' => $image->id,
                            'url' => $image->url,
                            'is_main' => $image->is_main,
                            'order' => $image->order,
                        ];
                    });
            }),
            'options' => $this->whenLoaded('options', function() {
                return $this->options->map(function($option) {
                    return [
                        'id' => $option->id,
                        'name' => $option->name,
                        'color_code' => $option->color_code,
                        'is_required' => $option->pivot->is_required,
                        'order' => $option->order,
                        'values' => $option->values->map(function($value) {
                            return [
                                'id' => $value->id,
                                'name' => $value->name,
                                'color_code' => $value->color_code,
                            ];
                        }),
                    ];
                });
            }),
            'variants' => $this->whenLoaded('activeVariants', function() {
                return $this->activeVariants->map(function($variant) {
                    $data = [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'price' => $variant->price,
                        'additional_cost' => $variant->additional_cost,
                        'stock_quantity' => $variant->getCurrentStock(),
                    ];

                    // Проверяем загруженность отношений напрямую
                    if ($variant->relationLoaded('unit')) {
                        $data['unit'] = [
                            'id' => $variant->unit->id,
                            'name' => $variant->unit->name,
                        ];
                    }

                    if ($variant->relationLoaded('images')) {
                        $data['images'] = $variant->images
                            ->sortBy('order')
                            ->values()
                            ->map(function($image) {
                                return [
                                    'id' => $image->id,
                                    'url' => $image->url,
                                    'is_main' => $image->is_main,
                                    'order' => $image->order,
                                ];
                            });
                    }

                    if ($variant->relationLoaded('optionValues')) {
                        $data['option_values'] = $variant->optionValues->map(function($value) {
                            return [
                                'id' => $value->id,
                                'name' => $value->name,
                                'option' => [
                                    'id' => $value->option->id,
                                    'name' => $value->option->name,
                                ],
                            ];
                        });
                    }

                    return $data;
                });
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

