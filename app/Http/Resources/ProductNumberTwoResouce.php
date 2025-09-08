<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Reverb\Loggers\Log;
use Str;

class ProductNumberTwoResouce extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdmin = $request->boolean('admin', false);
        $colors = collect();
        $sizes = collect();
        $available_variants = collect();

        if (!$isAdmin) {
            $collectedVariants = $this->variants ?? collect();


            $collectedVariants->map(function ($variant) use (&$colors, &$sizes, &$available_variants) {
                $size = null;
                $color = null;

//                \Illuminate\Support\Facades\Log::info('variant: ',  $variant);
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

                if ($variant->color_id) {
                    $color = [
                        'id' => $variant->color_id,
                        'name' => $variant->table_color ? $variant->table_color->name : null,
                        'code' => $variant->table_color ? $variant->table_color->code : null,
                    ];
                }

                if ($color) {
                    $colors->push($color);
                }

                $available_variants->push([
                    'id' => $variant->id,
                    'color_id' => $variant->color_id,
                    'size' => $variant->name,
                    'quantity' => $variant->stock,
                    'images' => $variant->images ? ImageResource::collection($variant->images) : null,
                ]);

            });
        }

        $totalStock = $isAdmin
            ? $this->inventory_balance
            : $this->stock_quantity;

        return [
            // Your custom structure based on the JSON you shared
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'description' => $this->description,
            'type' => $this->type,
            'default_unit_id' => $this->default_unit_id,
            'is_active' => $this->is_active,
            'has_variants' => $this->has_variants,
            'avg_rating' => $this->reviews_avg_rating ? round($this->reviews_avg_rating, 2) : null,
            // 'allow_preorder' => $this->allow_preorder,
            // 'after_purchase_processing_time' => $this->after_purchase_processing_time,
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->deleted_at,
            'price' => (float)$this->price,
            'old_price' => (float)$this->old_price,
            'stock_quantity' => (float)$totalStock,
            $this->mergeWhen($isAdmin, [
                'cost_price' => (float)$this->cost_price,
            ]),
            // 'currency' => $this->currency,
            // 'stock_quantity' => $this->stock_quantity,
            // 'min_order_quantity' => $this->min_order_quantity,
            // 'max_order_quantity' => $this->max_order_quantity,
            // 'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'discount_price' => (float)$this->discount_price,
            'discount_percentage' => (float)$this->discount_percentage,
            'total_discount' => (float)$this->total_discount,
            'discount_id' => $this->discount_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'weight' => (float)$this->weight,
            'length' => (float)$this->length,
            'width' => (float)$this->width,
            'height' => (float)$this->height,
            // 'image_path' => $this->image_path,
            'main_image' => $this->main_image ? new ImageResource($this->main_image) : null,
            'images' => ImageResource::collection($this->images ?? []),
            // 'colors' => ColorResource::collection($this->colors ?? []),
            $this->mergeWhen(!$isAdmin, [
                'available_variants' => $available_variants,
                'variants' => $sizes->unique()->values(),
                'colors' => $colors->unique()->values(),
            ]),
            $this->mergeWhen($isAdmin, [
                'variants' => ProductVariantNumberTwoResource::collection($this->variants ?? [])
            ]),
            'default_unit' => $this->defaultUnit ? new UnitResource($this->defaultUnit) : null,
            // 'discountable' => new DiscountableResource($this->whenLoaded('discountable')),
        ];
    }
}
