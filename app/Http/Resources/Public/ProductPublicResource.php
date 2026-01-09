<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\ImageResource;
use App\Http\Resources\UnitResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $colors = collect();
        $sizes = collect();
        $available_variants = collect();

        // Обработка вариантов для клиентов
        $collectedVariants = $this->variants ?? collect();

//        Log::debug([
//            'variants' => $collectedVariants->toArray(),
//        ]);

        $collectedVariants->each(function ($variant) use (&$colors, &$sizes, &$available_variants) {
            $size = null;

            // Извлекаем размер из названия варианта
            if (Str::contains($variant->name, '-')) {
                $segments = explode('-', $variant->name);
                $size = trim(end($segments));
            }

            if ($size) {
                $sizes->push([
                    'product_variant_id' => $variant->id,
                    'size' => $size,
                ]);
            }

            // Собираем цвета
            if ($variant->color_id) {
                $colors->push([
                    'id' => $variant->color_id,
                    'name' => $variant->table_color?->name,
                    'code' => $variant->table_color?->code,
                ]);
            }

            // Собираем доступные варианты
            $available_variants->push([
                'id' => $variant->id,
                'color_id' => $variant->color_id,
                'size' => $variant->name,
                'quantity' => $variant->stock_quantity,
                'price' => (float)$variant->price,
                'old_price' => $variant->old_price ? (float)$variant->old_price : null,
                'images' => $variant->images ? ImageResource::collection($variant->images) : null,
            ]);
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'display_order' => (int)$this->display_order,

            // Характеристики
            'absorbency_level' => (int)$this->absorbency_level,
            'fit_type' => $this->fit_type,

            // Цены и скидки
            'price' => (float)$this->price,
            'old_price' => (float)$this->old_price,
            'discount_price' => (float)$this->discount_price,
            'discount_percentage' => (float)$this->discount_percentage,
            'total_discount' => (float)$this->total_discount,

            // Статусы
            'is_active' => $this->is_active,
            'is_new' => $this->is_new,
            'has_variants' => $this->has_variants,

            // Остатки
            'stock_quantity' => (float)$this->stock_quantity,

            // Размеры для доставки
            'weight' => (float)$this->weight,
            'length' => (float)$this->length,
            'width' => (float)$this->width,
            'height' => (float)$this->height,

            // Рейтинг
            'avg_rating' => $this->reviews_avg_rating ? round($this->reviews_avg_rating, 2) : null,

            // Изображения
            'main_image' => $this->main_image ? new ImageResource($this->main_image) : null,
            'images' => ImageResource::collection($this->images ?? []),

            // Варианты (цвета, размеры)
            'available_variants' => $available_variants,
            'variants' => $sizes->unique()->values(),
            'colors' => $colors->unique()->values(),

            // Единица измерения
            'default_unit' => $this->defaultUnit ? new UnitResource($this->defaultUnit) : null,

            // Дополнительно
            'marketplace_links' => $this->marketplace_links ? json_decode($this->marketplace_links, true) : null,
        ];
    }
}
