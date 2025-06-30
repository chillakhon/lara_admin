<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImageResource;
use App\Models\Cart;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ProductsTrait;
use DB;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ProductsTrait;


    // function that addes multiple items to cart
    public function add_multiple_items_to_cart(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.color_id' => 'nullable|integer|exists:colors,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0'
        ]);

        $client = $request->user();

        if ($client instanceof \App\Models\User) {
            return response()->json([
                'success' => false,
                'message' => 'Клиент должен быть экземпляром модели Client, а не User.',
            ]);
        }

        $cart = Cart::firstOrCreate(
            ['client_id' => $client->id, 'status' => null],
            ['created_at' => now()]
        );

        $incomingItems = collect($validated['items']);

        $toKeep = $incomingItems->map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
            ];
        });

        $cart->items()->whereNot(function ($query) use ($toKeep) {
            foreach ($toKeep as $index => $item) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}(function ($subQuery) use ($item) {
                    $subQuery->where('product_id', $item['product_id'])
                        ->where(function ($q) use ($item) {
                            if ($item['product_variant_id'] === null) {
                                $q->whereNull('product_variant_id');
                            } else {
                                $q->where('product_variant_id', $item['product_variant_id']);
                            }
                        });
                });
            }
        })->delete();


        foreach ($validated['items'] as $item) {
            $cart->items()->updateOrCreate(
                [
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                ],
                [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'color_id' => $item['color_id'] ?? null,
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Items added to cart.']);
    }


    // function that addes single item to cart
    public function add_item_to_cart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'color_id' => 'nullable|integer|exists:colors,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0'
        ]);

        $client = $request->user();

        if ($client instanceof \App\Models\User) {
            return response()->json([
                'success' => false,
                'message' => 'Клиент должен быть экземпляром модели Client, а не User.',
            ]);
        }

        $cart = Cart::firstOrCreate(
            ['client_id' => $client->id, 'status' => null],
            ['created_at' => now()]
        );

        $cart->items()->updateOrCreate(
            [
                'product_id' => $validated['product_id'],
                'product_variant_id' => $validated['product_variant_id'] ?? null,
            ],
            [
                'quantity' => $validated['quantity'],
                'price' => $validated['price']
            ]
        );

        return response()->json(['success' => true, 'message' => 'Item added to cart.']);
    }

    private function sync($user, $found_items)
    {
        if (!$user || empty($found_items))
            return;

        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'status' => null],
            ['created_at' => now()]
        );

        $keysToKeep = collect($found_items)->map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
            ];
        });

        $cart->items()->whereNot(function ($query) use ($keysToKeep) {
            foreach ($keysToKeep as $index => $key) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}(function ($subQuery) use ($key) {
                    $subQuery->where('product_id', $key['product_id'])
                        ->where(function ($q) use ($key) {
                            if (is_null($key['product_variant_id'])) {
                                $q->whereNull('product_variant_id');
                            } else {
                                $q->where('product_variant_id', $key['product_variant_id']);
                            }
                        });
                });
            }
        })->delete();

        foreach ($found_items as $item) {
            $cart->items()->updateOrCreate(
                [
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null
                ],
                [
                    'price' => $item['price'],
                    'quantity' => 1
                ]
            );
        }
    }


    // function that finds product or product_variant 
    // from table when user refreshes his items after while
    // maybe between that duration of absence products or variants were deleted
    public function cart_items(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array'
        ]);

        $user = $request->user();

        $found_items = [];

        foreach ($validated['items'] as $item) {
            if (!is_null($item['product_variant_id'])) {
                $product_variant = ProductVariant
                    ::join('products', 'product_variants.product_id', 'products.id')
                    ->with([
                        'images' => function ($sql) {
                            $sql->orderBy("order", 'asc');
                        },
                    ])
                    ->where('product_variants.id', $item['product_variant_id'])
                    ->where('product_variants.product_id', $item['product_id'])
                    ->where('product_variants.is_active', true)
                    ->whereNull('product_variants.deleted_at')
                    ->whereNull('products.deleted_at')
                    ->select(['product_variants.*', 'products.description'])
                    ->first();

                if ($product_variant) {

                    $has_color = true;
                    if (!is_null($item['color_id'])) {
                        $find_color = DB::table('colorables')
                            ->where('colorable_type', ProductVariant::class)
                            ->where('colorable_id', $item['product_variant_id'])
                            ->where('color_id', $item['color_id'])
                            ->first();
                        if (!$find_color) {
                            $has_color = false;
                        }
                    }

                    if ($has_color)
                        $found_items[] = $this->get_product_variants_fields($product_variant);
                }

            } else if (!is_null($item['product_id'])) {
                $product = Product
                    ::with([
                        'images' => function ($sql) {
                            $sql->orderBy("order", 'asc');
                        },
                    ])
                    ->where('id', $item['product_id'])
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->first();
                if ($product) {
                    $has_color = true;
                    if (!is_null($item['color_id'])) {
                        $find_color = DB::table('colorables')
                            ->where('colorable_type', Product::class)
                            ->where('colorable_id', $item['product_id'])
                            ->where('color_id', $item['color_id'])
                            ->first();
                        if (!$find_color) {
                            $has_color = false;
                        }
                    }

                    if ($has_color)
                        $found_items[] = $this->get_product_fields($product);
                }
            }
        }

        $this->sync($user, $found_items);

        return response()->json([
            'success' => false,
            'items' => $found_items,
        ]);
    }

    protected function get_product_fields($product)
    {
        $this->applyDiscountToProduct($product);

        return [
            'product_id' => $product->id,
            'product_variant_id' => null,
            "name" => $product->name,
            "slug" => $product->slug,
            "description" => $product->description,
            "price" => $product->price,
            "old_price" => $product->old_price,
            "discount_percentage" => $product->discount_percentage,
            "total_discount" => $product->total_discount,
            "currency" => $product->currency,
            'barcode' => $product->barcode,
            "images" => ImageResource::collection($product->images),
        ];
    }

    protected function get_product_variants_fields($product_variant)
    {

        $this->applyDiscountToProduct($product_variant);

        return [
            'product_id' => $product_variant->product_id,
            'product_variant_id' => $product_variant->id,
            "name" => $product_variant->name,
            "slug" => $product_variant->slug,
            "description" => $product_variant->description,
            "price" => $product_variant->price,
            "old_price" => $product_variant->old_price,
            "discount_percentage" => $product_variant->discount_percentage,
            "total_discount" => $product_variant->total_discount,
            "currency" => $product_variant->currency,
            'barcode' => $product_variant->barcode,
            "images" => ImageResource::collection($product_variant->images),
        ];
    }
}
