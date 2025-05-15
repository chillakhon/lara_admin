<?php
namespace App\Traits;

use App\Models\InventoryBalance;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
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
                // 'options.values',
                // 'variants.optionValues.option',
                'variants' => function ($sql) {
                    $sql->whereNull("deleted_at")
                        ->with('unit')
                        ->with([
                            'images' => function ($sql) {
                                $sql->orderBy("order", 'asc');
                            }
                        ]);
                },
                'defaultUnit',
            ])
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
            ->latest();


        if ($request->get('type')) {
            $products->where('type', $request->get('type'));
        }

        if ($request->get('product_id')) {
            $products->where('id', $request->get('product_id'));
        }

        return $products;
    }

    public function solve_products_inventory(&$products = [])
    {
        $inventory_balances = InventoryBalance::get()
            ->keyBy(function ($item) {
                return $this->get_type_by_model($item->item_type) . '_' . $item->item_id;
            });

        foreach ($products as &$product) {
            $productKey = "Product_{$product->id}";

            $product->inventory_balance = 0.0;

            if (!empty($product['variants'])) {
                foreach ($product['variants'] as &$variant) {
                    $variantKey = "ProductVariant_{$variant->id}";
                    $variant_total_qty = $inventory_balances[$variantKey]['total_quantity'] ?? 0.0;
                    $variant->inventory_balance = $variant_total_qty;
                    $product->inventory_balance += $variant_total_qty;
                }
            } else {
                $product->inventory_balance = $inventory_balances[$productKey]['total_quantity'] ?? 0.0;
            }
        }
    }
}
