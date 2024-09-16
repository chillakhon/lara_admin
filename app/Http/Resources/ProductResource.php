<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $variants = $this->whenLoaded('variants', function () {
            return $this->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'article' => $variant->article,
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                    'size' => $this->getSizeForVariant($variant),
                    'color_options' => $this->getColorOptionsForVariant($variant),
                    'images' => $this->getImagesForVariant($variant),
                ];
            });
        });

        $sizes = $this->whenLoaded('variants', function () {
            return $this->variants->map(function ($variant) {
                return $this->getSizeForVariant($variant);
            })->filter()->unique('id')->values();
        });

        $dimensions = $this->getDimensionsFromSizes($sizes);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_available' => $this->is_available,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'variants' => $variants,
            'color_options' => $this->whenLoaded('colorOptions', function () {
                return $this->colorOptions->map(function ($colorOption) {
                    return [
                        'id' => $colorOption->id,
                        'title' => $colorOption->title,
                        'colors' => $colorOption->colorOptionValues->map(function ($colorValue) {
                            return [
                                'id' => $colorValue->color->id,
                                'title' => $colorValue->color->title,
                                'code' => $colorValue->color->code,
                                'images' => ImageResource::collection($colorValue->color->images),
                            ];
                        }),
                    ];
                });
            }),
            'sizes' => $sizes,
            'dimensions' => $dimensions,
        ];
    }

    private function getSizeForVariant($variant)
    {
        $size = $this->sizes->first(function ($size) use ($variant) {
            return Str::contains($variant->name, $size->name);
        });

        return $size ? ['id' => $size->id, 'name' => $size->name] : null;
    }

    private function getColorOptionsForVariant($variant)
    {
        return $variant->colorOptionValues->groupBy('pivot.color_option_id')
            ->map(function ($values, $optionId) {
                $colorOption = $this->colorOptions->find($optionId);
                return [
                    'id' => $optionId,
                    'title' => $colorOption ? $colorOption->title : '',
                    'color' => [
                        'id' => $values->first()->color->id,
                        'title' => $values->first()->color->title,
                        'code' => $values->first()->color->code,
                    ],
                ];
            })->values();
    }

    private function getImagesForVariant($variant)
    {
        return ImageResource::collection($this->images->where('pivot.product_variant_id', $variant->id));
    }

    private function getDimensionsFromSizes($sizes)
    {
        $lengths = [];
        $widths = [];
        $depths = [];

        foreach ($sizes as $size) {
            $dimensions = explode('x', $size['name']);
            if (count($dimensions) == 2) {
                $lengths[] = intval(trim($dimensions[0]));
                $widths[] = intval(trim($dimensions[1]));
            }
            if (count($dimensions) == 3) {
                $lengths[] = intval(trim($dimensions[0]));
                $widths[] = intval(trim($dimensions[1]));
                $depths[] = intval(trim($dimensions[2]));
            }
        }

        return [
            'lengths' => array_unique($lengths),
            'widths' => array_unique($widths),
            'depths' => array_unique($depths),
        ];
    }
}

