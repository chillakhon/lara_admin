<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductNumberTwoResouce;
use App\Models\Image as ImageModel;
use App\Models\InventoryBalance;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\MaterialService;
use App\Services\MoySklad\MoySkladHelperService;
use App\Traits\HelperTrait;
use App\Traits\ImageTrait;
use App\Traits\ProductsTrait;
use Arr;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Log;

class ProductController extends Controller
{
    use HelperTrait, ImageTrait, ProductsTrait;

    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function index(Request $request)
    {

        try {

            $isAdmin = $request->boolean('admin', false);

            $product_stock_sklad = [];
            if ($isAdmin) {
                $moySkaldController = new MoySkladController();
                $product_stock_sklad = $moySkaldController->get_products_stock();
            }

            // could not solve the problem with .inventoryBalance relation
            $products = $this->products_query($request);


            if ($request->get('product_id')) {

                $products = $products->first();
                if (!$products) {
                    return response()->json([
                        'success' => false,
                        'message' => "Продукт не найден."
                    ]);
                }


                $this->solve_products_inventory([$products], $product_stock_sklad, $isAdmin);
                $this->applyDiscountToProduct($products);

                return new ProductNumberTwoResouce($products);
            } else if ($request->boolean('paginate', true)) {
                $products = $products->paginate($request->get('per_page') ?? 10);

                $products->getCollection()->transform(function ($product) {
                    $product->image_path = $product->images->isNotEmpty() ? $product->images->first()->path : null;
                    unset($product->images);
                    return $product;
                });
                $this->solve_products_inventory($products, $product_stock_sklad, $isAdmin);
                $this->applyDiscountsToCollection($products->getCollection());
            } else {
                $products = $products->get();
                $this->solve_products_inventory($products, $product_stock_sklad, $isAdmin);
                $this->applyDiscountsToCollection($products);
            }


            // return response()->json($products);
            return ProductNumberTwoResouce::collection($products);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                "line" => $e->getLine(),
                "stackTrace" => $e->getTraceAsString(),
            ]);
        }
    }


    public function show(Product &$product)
    {
        $product->load([
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
        ]);

        foreach ($product->images as &$image) {
            $image->item_type = $this->get_type_by_model($image->item_type);
        }

        foreach ($product->variants as &$variant) {
            foreach ($variant['images'] as &$image) {
                $image->item_type = $this->get_type_by_model($image->item_type);
            }
        }

        return new ProductNumberTwoResouce($product);
    }

    // enhanced-dev branch
    public function price_history(Request $request, Product $product)
    {
        // TODO: logic for getting product's prices history
        if ($request->boolean('is_variant', false)) {
            $variant_price_history = PriceHistory
                ::where('item_type', ProductVariant::class)
                ->where('item_id', $request->get('id'))
                ->whereNull("deleted_at")
                ->orderBy('created_at', 'desc')
                ->with('item')
                ->paginate(10);

            foreach ($variant_price_history as &$price_history) {
                $price_history->item_type = $this->get_type_by_model($price_history->item_type);
            }

            return response()->json([
                'success' => true,
                'price_history' => $variant_price_history
            ]);
        } else {
            $product = Product::where('id', $request->get('id'))->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => "Продукт не найден!",
                ]);
            }

            $product_variant_ids = ProductVariant::where('product_id', $product->id)->pluck('id')->toArray();

            $product_price_history = PriceHistory
                ::where(function ($sql) use ($product_variant_ids, $product, $request) {
                    $sql->where(function ($sql2) use ($product) {
                        $sql2->where('item_type', Product::class)
                            ->where('item_id', $product->id);
                    });

                    if ($request->boolean('get_with_variants', false)) {
                        $sql->orWhere(function ($sql2) use ($product_variant_ids) {
                            $sql2->where('item_type', ProductVariant::class)
                                ->whereIn('item_id', $product_variant_ids);
                        });
                    }
                })
                ->whereNull("deleted_at")
                ->orderBy('created_at', 'desc')
                ->with('item')
                ->paginate(10);

            foreach ($product_price_history as &$price_history) {
                $price_history->item_type = $this->get_type_by_model($price_history->item_type);
            }

            return response()->json([
                'success' => true,
                'price_history' => $product_price_history
            ]);
        }
    }


    // enhanced-dev branch
    public function warehouse_history(Request $request, Product $product)
    {
        // TODO: logic for getting product's qty history from warehouse
    }

    // enhanced-dev branch
    public function price_history_create(Request $request, $previous_price, $product = null, $variant = null)
    {

        if (!is_null($previous_price)) {
            $previous_price = $previous_price === -1 ? null : $previous_price;
            if ($product && $product->price != $previous_price) {
                PriceHistory::create([
                    'user_id' => $request->user()->id,
                    'item_type' => Product::class,
                    'item_id' => $product->id,
                    'price_from' => $previous_price,
                    'price_to' => $product->price,
                    "created_at" => now(),
                ]);
            }

            if ($variant && $variant->price != $previous_price) {
                PriceHistory::create([
                    'user_id' => $request->user()->id,
                    'item_type' => ProductVariant::class,
                    'item_id' => $variant->id,
                    'price_from' => $previous_price,
                    'price_to' => $variant->price,
                    "created_at" => now(),
                ]);
            }

        }
    }

    public function store(Request $request)
    {
        $validated = $this->validate_of_product($request);

        DB::beginTransaction();

        $moySkladController = new MoySkladController();
        $createdMsProduct = null;
        // $createdMsVariantIds = [];

        try {
            $product = Product::create(array_merge(
                $validated,
                [
                    // 'uuid' => Str::uuid(),
                    'slug' => Str::slug($validated['name']),
                    'sku' => Str::slug($validated['name']),
                    'created_at' => now(),
                ]
            ));

            $colorIds = collect($validated['colors'] ?? [])->pluck('id');

            $product->colors()->attach($colorIds);

            $createdMsProduct = $moySkladController->create_product($product);

            $product->update([
                'uuid' => $createdMsProduct->id,
            ]);

            // -1 means that its creating for the first time and you have to put null instead
            $this->price_history_create($request, -1, $product);

            if ($request->hasFile('product_images')) {
                foreach ($request->file('product_images') as $key => $productImage) {
                    $this->save_images($productImage, Product::class, $product->id, $key);
                }
            }

            // $product->categories()->sync($validated['categories']);
            if (count($validated['variants'] ?? []) >= 1) {

                $createdVariants = [];

                foreach ($validated['variants'] as $variantData) {
                    $uuid = $variantData['local_uuid'] ?? null;

                    if (!$uuid) {
                        continue;
                    }

                    $cleanVariantData = Arr::except($variantData, ['local_uuid']);
                    $cleanVariantData['product_id'] = $product->id;
                    $cleanVariantData['weight'] = $product->weight;
                    $cleanVariantData['length'] = $product->length;
                    $cleanVariantData['width'] = $product->width;
                    $cleanVariantData['height'] = $product->height;
                    $cleanVariantData['colors'] = $validated['colors'] ?? []; // product's colors
                    $cleanVariantData['sku'] = Str::slug($variantData['name']) . '-' . $product->id;
                    // temp value for syncing with MoySklad
                    $cleanVariantData['code'] = (string)rand(1000000000, 9999999999);
                    $cleanVariantData['created_at'] = now();

                    $created_variant = ProductVariant::create($cleanVariantData);

                    $colorIds = collect($cleanVariantData['colors'] ?? [])->pluck('id');

                    $created_variant->colors()->attach($colorIds);
                    // -1 means that its creating for the first time and you have to put null instead
                    $this->price_history_create($request, -1, null, $created_variant);
                    if ($request->hasFile("variant_images_" . $uuid)) {
                        foreach ($request->file("variant_images_" . $uuid) as $key => $variantImage) {
                            $this->save_images($variantImage, ProductVariant::class, $created_variant->id, $key);
                        }
                    }

                    $createdVariants[] = $created_variant;

                    // $msProductVariant = $moySkladController->create_modification($created_variant, $createdMsProduct);

                    // $created_variant->update([
                    //     'uuid' => $msProductVariant->id,
                    // ]);

                    // $createdMsVariantIds[] = $msProductVariant->id;
                }

                $massCreatedModifications = $moySkladController->mass_variant_creation_and_update(
                    $createdVariants,
                    $createdMsProduct,
                );

                // update local variants after mass creation
                foreach ($createdVariants as $key => $cv) {
                    if (array_key_exists($cv->code, $massCreatedModifications)) {
                        $cv->update([
                            'uuid' => $massCreatedModifications[$cv->code],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            // foreach ($createdMsVariantIds as $key => $value) {
            //     $moySkladController->delete_variant($value);
            // }

            // I Guess deleting the product will delete it's modifications in moySklad
            if ($createdMsProduct) {
                $moySkladController->delete_product($createdMsProduct->id);
            }

            return response()->json([
                'message' => 'Failed to create product',
                "product_exist" => $createdMsProduct,
                'error' => $e->getMessage(),
                "line" => $e->getLine(),
                "stackTrace" => $e->getTraceAsString(),
            ], 500);
        }
    }

    private function baseProductRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:simple,material',
            'weight' => 'required|numeric|min:0',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'default_unit_id' => 'required|exists:units,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
            'allow_preorder' => 'boolean',
            'after_purchase_processing_time' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_order_quantity' => 'nullable|integer|min:1',
            'max_order_quantity' => 'nullable|integer|min:1',
            'discount_price' => 'nullable|numeric|min:0',
            'colors' => 'nullable|array',
            'variants' => 'nullable|array',
        ];
    }

    public function validate_of_product(Request $request)
    {
        $rules = array_merge($this->baseProductRules(), [
            'sku' => 'nullable|string|unique:products,sku',
            'barcode' => 'nullable|string|unique:products,barcode',
        ]);
        return $request->validate($rules);
    }

    public function validate_of_product_update(Request $request, $id)
    {
        $rules = array_merge($this->baseProductRules(), [
            'sku' => ['nullable', 'string', Rule::unique('products', 'sku')->ignore($id)],
            'slug' => ['nullable', 'string', Rule::unique('products', 'slug')->ignore($id)],
            'barcode' => ['nullable', 'string', Rule::unique('products', 'barcode')->ignore($id)],
        ]);
        return \Validator::make($request->all(), $rules)->validate();
    }


    public function update(Request $request, $id)
    {
        $validated = $this->validate_of_product_update($request, $id);

        DB::beginTransaction();

        $moyskadController = new MoySkladController();
        $msProduct = null;
        // this is for local data after creation
        $createdVariants = [];

        // this is for moysklad data that comes after creation
        $createdVariantsIds = [];
        $product_variant_for_deletion_ids = [];
        // $productVariantDeletionReached = false;

        try {
            $product = Product::where('id', $id)->firstOrFail();

            $prev_price = $product->price;

            $product->update(array_merge(
                $validated,
                [
                    'slug' => Str::slug($validated['name']),
                    'sku' => Str::slug($validated['name']),
                    'updated_at' => now(),
                ]
            ));

            $this->price_history_create($request, $prev_price, $product);

            $this->update_product_images($request, $product);

            $colorIds = collect($validated['colors'] ?? [])->pluck('id');

            $product->colors()->sync($colorIds);

            $product->refresh();

            $msProduct = $moyskadController->update_product($product);

            // Handle variants
            $incomingVariantIds = collect($validated['variants'] ?? [])->pluck('id')->filter()->toArray();

            // Delete removed variants
            $prod_variant_check = ProductVariant::where('product_id', $product->id);
            if (!empty($incomingVariantIds)) {
                $prod_variant_check->whereNotIn('id', $incomingVariantIds);
            }

            $product_variant_for_deletion_ids = (clone $prod_variant_check)
                ->withTrashed()
                ->pluck('uuid')->toArray();

            Log::info("test all ids", $product_variant_for_deletion_ids);

            $prod_variant_check = $prod_variant_check->get()
                ->each(function ($variant) {
                    $variantImages = ImageModel::where('item_type', ProductVariant::class)
                        ->where('item_id', $variant->id)
                        ->get();

                    foreach ($variantImages as $image) {
                        foreach (['original', 'lg', 'md', 'sm'] as $size) {
                            $path = storage_path("app/public/products/{$size}_{$image->path}");
                            if (File::exists($path)) {
                                File::delete($path);
                            }
                        }
                        $image->delete();
                    }

                    $variant->colors()->detach();
                    $variant->update([
                        'sku' => null,
                    ]);
                    $variant->delete();
                });

            // Add or update variants
            foreach (($validated['variants'] ?? []) as $variantData) {
                $uuid = $variantData['local_uuid'] ?? null;

                if (!$uuid) {
                    continue;
                }

                $cleanVariantData = Arr::except($variantData, ['local_uuid', 'id']);
                // setting the values of product's weight, length, width, height
                $cleanVariantData['product_id'] = $product->id;
                $cleanVariantData['weight'] = $product->weight;
                $cleanVariantData['length'] = $product->length;
                $cleanVariantData['width'] = $product->width;
                $cleanVariantData['height'] = $product->height;
                $cleanVariantData['colors'] = $validated['colors'] ?? [];
                $variant_colors_ids = collect($cleanVariantData['colors'] ?? [])->pluck('id');

                if (!empty($variantData['id'])) {
                    $variant = ProductVariant::where('id', $variantData['id'])->firstOrFail();
                    $previous_price = $variant->price;
                    $cleanVariantData = Arr::except($cleanVariantData, ['sku']);
                    $variant->update($cleanVariantData);
                    $variant->colors()->sync($variant_colors_ids);
                    $this->price_history_create($request, $previous_price, null, $variant);
                    $createdVariants[] = $variant;
                    // $value = $variant->refresh();
                    // if modification updates or failes it's not so serious
                    // $moyskadController->update_modification($variant);
                } else {
                    // temp value for syncing with MoySklad
                    $cleanVariantData['code'] = (string)rand(1000000000, 9999999999);
                    $baseSku = Str::slug($variantData['name']);
                    $sku = $baseSku . '-' . $product->id;

                    // если даже такое sku уже есть — добавим рандом
                    if (ProductVariant::where('sku', $sku)->exists()) {
                        $sku .= '-' . Str::random(4);
                    }
                    $cleanVariantData['sku'] = $sku;
                    $variant = ProductVariant::create($cleanVariantData);
                    // -1 means that its creating for the first time and you have to put null instead
                    $variant->colors()->attach($variant_colors_ids);
                    $this->price_history_create($request, -1, null, $variant);
                    $variant = $variant->refresh();
                    $createdVariants[] = $variant;
                    // but it's serious when modification creates in moySklad
                    // something goes wrong in php code
                    // $msProductVariant = $moyskadController->create_modification($variant, $msProduct);
                    // $createdVariantsIds[] = $msProductVariant->id;
                }

                $this->update_variant_images($request, $variant, $uuid);
            }



            if ($createdVariants) {
                $massCreatedModifications = $moyskadController->mass_variant_creation_and_update(
                    $createdVariants,
                    $msProduct,
                );


                // update local variants after mass creation with uuid
                foreach ($createdVariants as $key => $cv) {
                    if (array_key_exists($cv->code, $massCreatedModifications)) {
                        $cv->update([
                            'uuid' => $massCreatedModifications[$cv->code],
                        ]);
                        $createdVariantsIds[] = $massCreatedModifications[$cv->code];
                    }
                }
            }
            // if everything goes perfect
            // we will delete all deleted variants from moysklad too
            // BUT! remember if there is any supplier invoice that were made for
            // specific variant or product you cant delete that
            if ($product_variant_for_deletion_ids) {
                // $productVariantDeletionReached = true;
                $moyskadController->mass_variant_deletion($product_variant_for_deletion_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Товар успешно обновлён',
                'product' => $product
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            // that is why if something goes wrong we will delete only created variants
            $moyskadController->mass_variant_deletion($createdVariantsIds);


            return response()->json([
                "error_line" => $e->getLine(),
                'message' => 'Не удалось обновить товар',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    private function update_product_images(
        Request $request,
                $product
    )
    {

        // previois saved images of the products
        $existingImages = $request->get('images', []); // e.g., ["image_12_123456.jpg"]
        $currentImages = ImageModel::where('item_id', $product->id)
            ->where('item_type', Product::class)
            ->get();

        foreach ($currentImages as $image) {
            if (!in_array($image->path, $existingImages)) {
                // Delete image files
                foreach (['original', 'lg', 'md', 'sm'] as $size) {
                    $path = storage_path("app/public/products/{$size}_{$image->path}");
                    if (File::exists($path)) {
                        File::delete($path);
                    }
                }
                $image->delete();
                continue;
            }
            $normalizedPath = str_replace('.', '_', $image->path);
            $key = "product_image_path_" . $normalizedPath;
            $position = (int)$request->get($key);

            $image->update([
                'order' => $position,
                'is_main' => (!is_null($position) && $position == 0) ? true : false,
            ]);
        }

        if ($request->hasFile('product_images')) {
            foreach ($request->file('product_images') as $key => $productImage) {
                $key = "product_image_file_" . $key;
                $position = (int)$request->get($key);
                $this->save_images($productImage, Product::class, $product->id, $position);
            }
        }
    }

    private function update_variant_images(Request $request, $variant, $uuid)
    {
        $existingVariantImages = ImageModel::where('item_type', ProductVariant::class)
            ->where('item_id', $variant->id)
            ->get();

        $keptImages = $request->get("variant_name_images_" . $uuid, []); // incoming retained image names

        foreach ($existingVariantImages as $image) {
            if (!in_array($image->path, $keptImages)) {
                foreach (['original', 'lg', 'md', 'sm'] as $size) {
                    $path = storage_path("app/public/products/{$size}_{$image->path}");
                    if (File::exists($path)) {
                        File::delete($path);
                    }
                }
                $image->delete();
                continue;
            }
            $normalizedPath = str_replace('.', '_', $image->path);
            $key = "variant_" . $uuid . "_image_path_" . $normalizedPath;
            $position = (int)$request->get($key);

            $image->update([
                'order' => $position,
                'is_main' => (!is_null($position) && $position == 0) ? true : false,
            ]);
        }

        if ($request->hasFile("variant_images_" . $uuid)) {
            foreach ($request->file("variant_images_" . $uuid) as $key => $variantImage) {
                $key = "variant_" . $uuid . "_image_file_" . $key;
                $position = (int)$request->get($key);
                $this->save_images(
                    $variantImage,
                    ProductVariant::class,
                    $variant->id,
                    $position
                );
            }
        }

    }


    public function destroy(Product $product)
    {
        $moySkladController = new MoySkladController();

        $deleteResult = $moySkladController->delete_product($product->uuid);

        if (!$deleteResult['success']) {
            return response()->json($deleteResult);
        }

        $product->update([
            'slug' => null,
            "sku" => null,
        ]);

        $product->variants()->update([
            'sku' => null,
        ]);

        $product->delete();
        $product->variants()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
            "delete_result" => $deleteResult
        ]);
    }

    public function restoreProduct(Request $request)
    {
        $product = Product::where('id', $request->get('id'))->withTrashed()->first();
        $product->update(['deleted_at' => null]);
        $product->variants()->withTrashed()->update(['deleted_at' => null]);
        return response()->json(['success' => true, 'message' => 'Product restored successfully']);
    }

    public function storeImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:5120',
        ]);

        $uploadedImages = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('products', 'public');
            $imageModel = $product->images()->create(['path' => $path, 'url' => Storage::url($path)]);
            $uploadedImages[] = $imageModel;
        }

        return response()->json(['message' => 'Images uploaded successfully', 'images' => $uploadedImages]);
    }


    public function generateVariants(Request $request, Product $product)
    {
        $validated = $request->validate([
            'variants' => 'required|array',
            'variants.*.name' => 'required|string',
            'variants.*.sku' => 'required|string|unique:product_variants,sku',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.option_values' => 'required|array',
            'variants.*.option_values.*' => 'exists:option_values,id',
        ]);

        try {
            DB::beginTransaction();

            $createdVariants = [];

            foreach ($validated['variants'] as $variantData) {
                $variant = $product->variants()->create([
                    'name' => $variantData['name'],
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'type' => $product->type,
                    'unit_id' => $product->default_unit_id,
                    'is_active' => true,
                ]);

                $variant->optionValues()->sync($variantData['option_values']);

                $createdVariants[] = $variant;
            }

            DB::commit();

            return response()->json([
                'message' => 'Variants generated successfully',
                'variants' => $createdVariants,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to generate variants',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function addComponent(Request $request, Product $product)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        $component = $product->components()->create($validated);

        return response()->json([
            'message' => 'Component added successfully.',
            'component' => $component
        ], 201);
    }


    public function removeComponent(Product $product, $componentId)
    {
        $product->components()->findOrFail($componentId)->delete();

        return response()->json([
            'message' => 'Component removed successfully.'
        ], 200);
    }


    public function calculateCost(Product $product)
    {
        $cost = $this->materialService->calculateProductCost($product);

        return response()->json([
            'message' => "The calculated cost is: $cost",
            'cost' => $cost
        ], 200);
    }

}
