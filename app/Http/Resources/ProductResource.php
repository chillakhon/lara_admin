<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\DiscountService;
use Illuminate\Support\Collection;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        static $discountService = null;
        if ($discountService === null) {
            $discountService = app(DiscountService::class);
        }

        $applicableDiscounts = collect($discountService->calculateDiscounts($this->resource));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'stock_quantity' => $this->stock_quantity,
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'sku' => $this->sku,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight' => $this->weight,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'has_variants' => $this->has_variants,
            'allow_preorder' => $this->allow_preorder,
            'default_unit' => $this->whenLoaded('defaultUnit', function () {
                return [
                    'id' => $this->defaultUnit->id,
                    'name' => $this->defaultUnit->name,
                ];
            }),
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                    ];
                });
            }),
            'main_image' => $this->whenLoaded('images', function () {
                $mainImage = $this->images
                    ->sortBy('order')
                    ->firstWhere('is_main', true) ??
                    $this->images->sortBy('order')->first();

                return $mainImage ? [
                    'id' => $mainImage->id,
                    'url' => $mainImage->url,
                ] : null;
            }),
            'price_range' => $this->whenLoaded('activeVariants', function () use ($discountService) {
                $prices = $this->activeVariants->map(function ($variant) use ($discountService) {
                    $variantDiscounts = collect($discountService->calculateDiscounts($this->resource, $variant));
                    $originalPrice = $variant->price;
                    $finalPrice = $originalPrice;

                    // Применяем все подходящие скидки
                    foreach ($variantDiscounts as $discount) {
                        switch ($discount->type) {
                            case 'percentage':
                                $finalPrice -= ($originalPrice * $discount->value / 100);
                                break;
                            case 'fixed':
                                $finalPrice -= $discount->value;
                                break;
                            case 'special_price':
                                $finalPrice = $discount->value;
                                break;
                        }
                    }

                    return [
                        'original' => $originalPrice,
                        'final' => max($finalPrice, 0)
                    ];
                });

                if ($prices->isEmpty()) {
                    return null;
                }

                return [
                    'min' => [
                        'original' => $prices->min('original'),
                        'final' => $prices->min('final')
                    ],
                    'max' => [
                        'original' => $prices->max('original'),
                        'final' => $prices->max('final')
                    ],
                    'has_discount' => $prices->some(fn($price) => $price['final'] < $price['original'])
                ];
            }),
            'variants' => $this->whenLoaded('activeVariants', function () use ($discountService) {
                return $this->activeVariants->map(function ($variant) use ($discountService) {
                    $variantDiscounts = collect($discountService->calculateDiscounts($this->resource, $variant));
                    $originalPrice = $variant->price;
                    $finalPrice = $originalPrice;

                    // Применяем все подходящие скидки
                    foreach ($variantDiscounts as $discount) {
                        switch ($discount->type) {
                            case 'percentage':
                                $finalPrice -= ($originalPrice * $discount->value / 100);
                                break;
                            case 'fixed':
                                $finalPrice -= $discount->value;
                                break;
                            case 'special_price':
                                $finalPrice = $discount->value;
                                break;
                        }
                    }

                    return [
                        'id' => $variant->id,
                        'price' => [
                            'original' => $originalPrice,
                            'final' => max($finalPrice, 0),
                            'has_discount' => $finalPrice < $originalPrice
                        ],
                        'stock_quantity' => $variant->stock_quantity,
                        'unit' => $variant->unit->name ?? null,
                        'option_values' => $variant->optionValues->map(function ($optionValue) {
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
                        'images' => $variant->images->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'url' => $image->url,
                                'path' => $image->path,
                            ];
                        }),
                        'discounts' => $variantDiscounts->map(function ($discount) {
                            return [
                                'id' => $discount->id,
                                'name' => $discount->name,
                                'type' => $discount->type,
                                'value' => $discount->value,
                                'starts_at' => $discount->starts_at,
                                'ends_at' => $discount->ends_at
                            ];
                        })
                    ];
                });
            }),
            'discounts' => $applicableDiscounts->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'type' => $discount->type,
                    'value' => $discount->value,
                    'starts_at' => $discount->starts_at,
                    'ends_at' => $discount->ends_at
                ];
            }),
            'rating' => [
                'average' => round($this->average_rating, 1),
                'count' => $this->reviews_count
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
