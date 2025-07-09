<?php
namespace App\Traits;

use App\Models\InventoryBalance;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait ProductsTrait
{
    public function products_query(Request $request): Builder
    {
        $products = Product
            ::with([
                'images' => function ($sql) {
                    $sql->orderBy("order", 'asc');
                },
                'colors:id,name,code',
                // 'options.values',
                // 'variants.optionValues.option',
                'variants' => function ($sql) {
                    $sql->whereNull("deleted_at")
                        ->with([
                            'unit',
                            'colors:id,name,code',
                            'images' => function ($sql) {
                                $sql->orderBy("order", 'asc');
                            }
                        ]);
                },
                'defaultUnit',
            ])
            ->withAvg([
                'reviews' => function ($query) {
                    $query->where('reviewable_type', Product::class)
                        ->where('is_published', true)
                        ->where('is_verified', true)
                        ->where('is_spam', false)
                        ->whereNull('deleted_at');
                }
            ], 'rating')
            ->when($request->get('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('categories', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('variants', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")->whereNull('deleted_at');
                    });
            })
            ->when($request->get('category_id'), function ($query, $categoryId) {
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                });
            })

            // searching by color name
            ->when($request->get('color_name'), function ($query, $color_name) {
                $query->whereHas('colors', function ($sql) use ($color_name) {
                    $sql->where('colors.name', $color_name);
                })->orWhereHas('variants', function ($sql) use ($color_name) {
                    $sql->whereHas('colors', function ($sql2) use ($color_name) {
                        $sql2->where('colors.name', $color_name);
                    });
                });
            })


            // searhcin by color id
            ->when($request->get('color_id'), function ($query, $color_id) {
                $query->whereHas('colors', function ($sql) use ($color_id) {
                    $sql->where('colors.id', $color_id);
                })->orWhereHas('variants', function ($sql) use ($color_id) {
                    $sql->whereHas('colors', function ($sql2) use ($color_id) {
                        $sql2->where('colors.id', $color_id);
                    });
                });
            })

            ->latest();


        if ($request->get('type', 'simple')) {
            $products->where('type', $request->get('type', 'simple'));
        }

        if ($request->get('product_id')) {
            $products->where('id', $request->get('product_id'));
        }

        return $products;
    }

    public function solve_products_inventory($products = [], $product_stock_sklad = [])
    {
        // In MoySklad, each modification (variant) can have its own stock quantity,
        // and the main product can also have its own stock.
        // Therefore, to get the total available stock, we need to sum both.
        foreach ($products as &$product) {
            // $product->inventory_balance = 0.0;
            $product->inventory_balance = $product_stock_sklad[$product->uuid]['stock'] ?? 0.0;

            if (!empty($product['variants'])) {
                foreach ($product['variants'] as &$variant) {
                    $variant_total_qty = $product_stock_sklad[$variant->uuid]['stock'] ?? 0.0;
                    $variant->inventory_balance = $variant_total_qty;
                    $product->inventory_balance += $variant_total_qty;
                }
            }
            // else {
            //     $product->inventory_balance = $inventory_balances[$product->uuid]['stock'] ?? 0.0;
            // }
        }

        // $inventory_balances = InventoryBalance::get()
        //     ->keyBy(function ($item) {
        //         return $this->get_type_by_model($item->item_type) . '_' . $item->item_id;
        //     });

        // foreach ($products as &$product) {
        //     $productKey = "Product_{$product->id}";

        //     $product->inventory_balance = 0.0;

        //     if (!empty($product['variants'])) {
        //         foreach ($product['variants'] as &$variant) {
        //             $variantKey = "ProductVariant_{$variant->id}";
        //             $variant_total_qty = $inventory_balances[$variantKey]['total_quantity'] ?? 0.0;
        //             $variant->inventory_balance = $variant_total_qty;
        //             $product->inventory_balance += $variant_total_qty;
        //         }
        //     } else {
        //         $product->inventory_balance = $inventory_balances[$productKey]['total_quantity'] ?? 0.0;
        //     }
        // }
    }


    // FOR SOLVING DISCOUNTS
    public function applyDiscountsToCollection(Collection $products): void
    {
        foreach ($products as $product) {
            $this->applyDiscountToProduct($product);

            if ($product->relationLoaded('variants')) {
                foreach ($product->variants as $variant) {
                    $this->applyDiscountToProduct($variant);
                }
            }
        }
    }

    public function applyDiscountToProduct($model): void
    {
        // Support both Product and ProductVariant
        $price = $model->price;
        $oldPrice = $model->old_price;
        $discount = $model->discount();
        // $model->tempHEHEHE = $discount ? $discount : "NO";
        // return;

        $finalPrice = $price;
        $percentage = null;
        $totalDiscount = null;

        if ($discount && $discount->is_active) {
            if ($discount->type === 'fixed') {
                $totalDiscount = $discount->value;
                $finalPrice = max(0, $price - $totalDiscount);
                $percentage = $price > 0 ? round(($totalDiscount / $price) * 100, 2) : null;
            } elseif ($discount->type === 'percentage') {
                $percentage = $discount->value;
                $totalDiscount = round(($percentage / 100) * $price, 2);
                $finalPrice = max(0, $price - $totalDiscount);
            }
            $model->old_price = $price;
            $model->discount_id = $discount->id;
        } elseif ($oldPrice && $oldPrice > $price) {
            $totalDiscount = $oldPrice - $price;
            $percentage = $oldPrice > 0 ? round(($totalDiscount / $oldPrice) * 100, 2) : null;
            // setting null to discount_id if discount is null
            $model->discount_id = null;
        } else {
            // setting null to discount_id if discount is null
            $model->discount_id = null;
        }

        // $model->final_price = $finalPrice;
        $model->price = $finalPrice;
        $model->discount_percentage = $percentage;
        $model->total_discount = $totalDiscount;
    }

    public function calculateWeightAndVolume($weight, $length, $width, $height, $defaultUnit): array
    {
        // Преобразуем вес в граммы
        $weightInGrams = match ($defaultUnit?->id) {
            3 => $weight * 1000,     // кг → г
            6 => $weight,            // г
            10 => $weight / 1000,     // мг → г
            default => $weight,      // Assume grams
        };

        // Преобразуем объём в м³ (если всё в см)
        $volumeInM3 = 0;
        if ($length && $width && $height) {
            $volumeInM3 = ($length * $width * $height) / 1_000_000;
        }

        return [
            'weight' => round($weightInGrams, 3),
            'volume' => round($volumeInM3, 6),
        ];
    }
}
