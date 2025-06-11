<?php

namespace App\Services\MoySklad;

use App\Http\Controllers\Api\Admin\MoySkladController;
use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use Evgeek\Moysklad\MoySklad;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Str;

class ProductsAndVariantsSyncWithMoySkladService
{
    private MoySklad $moySklad;
    private $token;
    private $baseURL = "https://api.moysklad.ru/api/remap/1.2";

    public function __construct()
    {

        $moyskadSettings = DeliveryServiceSetting
            ::where('service_name', 'moysklad')
            ->first();

        if (!$moyskadSettings) {
            throw new Exception("Настройки для МойСклад не найдены. Пожалуйста, настройте сервис в админке.");
        }

        $this->token = $moyskadSettings->token;
        $this->moySklad = new MoySklad(["{$moyskadSettings->token}"]);
    }


    public function sync_products_with_moysklad()
    {
        $moySkladService = new MoySkladHelperService();
        $moySkladController = new MoySkladController();

        $products = $moySkladService->get_products()->rows ?? [];
        $variants = $moySkladService->get_product_variants()->rows ?? [];
        $stock = $moySkladService->check_stock();

        $updatedCreatedProductUUID = [];

        // Index variants by product UUID
        $variantsGrouped = collect($variants)->groupBy(fn($v) => optional($v->product->meta)->href ?? '');

        foreach ($products as $productData) {

            $stockQty = $stock[$productData->id]['stock'] ?? 0.0;

            $slug = Str::slug($productData->name ?? '');

            $updatedCreatedProductUUID[] = $productData->id;

            $product = Product::where('uuid', $productData->id)->first();

            if (!$product) {
                $product = Product::where('slug', $slug)->first();
            }

            if ($product) {
                $product->update([
                    'uuid' => $productData->id, // обновим uuid если не был установлен
                    'name' => $productData->name ?? '',
                    'description' => $productData->description ?? null,
                    'slug' => $slug,
                    'price' => ($productData->salePrices[0]->value ?? 0) / 100,
                    'cost_price' => ($productData->buyPrice->value ?? 0) / 100,
                    'barcode' => $productData->barcodes[0]->ean13 ?? null,
                    'stock_quantity' => $stockQty,
                    'sku' => $slug,
                    'weight' => $productData->weight ?? 0,
                    'currency' => 'RUB',
                    'has_variants' => $productData->variantsCount > 0,
                ]);
            } else {
                $product = Product::create([
                    'uuid' => $productData->id,
                    'name' => $productData->name ?? '',
                    'description' => $productData->description ?? null,
                    'slug' => $slug,
                    'price' => ($productData->salePrices[0]->value ?? 0) / 100,
                    'cost_price' => ($productData->buyPrice->value ?? 0) / 100,
                    'barcode' => $productData->barcodes[0]->ean13 ?? null,
                    'stock_quantity' => $stockQty,
                    'sku' => $slug,
                    'weight' => $productData->weight ?? 0,
                    'currency' => 'RUB',
                    'has_variants' => $productData->variantsCount > 0,
                ]);
            }

            $productHref = $productData->meta->href;

            // Обновим/создадим варианты, если есть
            foreach ($variantsGrouped[$productHref] ?? [] as $variantData) {

                $variantStockQty = $stock[$variantData->id]['stock'] ?? 0.0;
                $variantSlug = Str::slug($variantData->name ?? '');


                $variant = ProductVariant::where('uuid', $variantData->id)->first();

                if (!$variant) {
                    $variant = ProductVariant::where('sku', $variantSlug)
                        ->where('product_id', $product->id) // важно уточнить товар
                        ->first();
                }

                if ($variant) {
                    $variant->update([
                        'uuid' => $variantData->id,
                        'product_id' => $product->id,
                        'name' => $variantData->name ?? '',
                        'sku' => $variantSlug,
                        'barcode' => $variantData->barcodes[0]->ean13 ?? null,
                        'price' => ($variantData->salePrices[0]->value ?? 0) / 100,
                        'cost_price' => null,
                        'stock' => $variantStockQty,
                        'weight' => $productData->weight ?? 0,
                        'type' => 'simple',
                        'is_active' => true,
                    ]);
                } else {
                    ProductVariant::create([
                        'uuid' => $variantData->id,
                        'product_id' => $product->id,
                        'name' => $variantData->name ?? '',
                        'sku' => $variantSlug,
                        'barcode' => $variantData->barcodes[0]->ean13 ?? null,
                        'price' => ($variantData->salePrices[0]->value ?? 0) / 100,
                        'cost_price' => null,
                        'stock' => $variantStockQty,
                        'weight' => $productData->weight ?? 0,
                        'type' => 'simple',
                        'is_active' => true,
                    ]);
                }
            }
        }

        $unsyncedProducts = Product
            ::where(function (Builder $query) use ($updatedCreatedProductUUID) {
                $query->whereNull('uuid')
                    ->orWhereNotIn('uuid', $updatedCreatedProductUUID);
            })->get();


        foreach ($unsyncedProducts as $key => $unsyncedProduct) {
            $msProduct = null;
            if ($moySkladController->check_product_for_existence($unsyncedProduct->uuid)) {
                $msProduct = $moySkladController->update_product($unsyncedProduct);
            } else {
                $msProduct = $moySkladController->create_product($unsyncedProduct);
            }

            if ($msProduct) {
                $unsyncedProduct->update([
                    'uuid' => $msProduct->id,
                ]);

                $variants = ProductVariant::where('product_id', $unsyncedProduct->id)->get();

                // update those variants where code are null
                // because code will be necessary when we to synchronize our product_variants with server
                foreach ($variants as $key => $tempVar) {
                    if (!$tempVar->code) {
                        $tempVar->update([
                            'code' => (string) rand(1000000000, 9999999999),
                        ]);
                    }
                }

                if (count($variants) >= 1) {
                    $massCreatedModifications = $moySkladController->mass_variant_creation_and_update($variants, $msProduct);

                    foreach ($variants as $key => $cv) {
                        if (array_key_exists($cv->code, $massCreatedModifications)) {
                            $cv->update([
                                'uuid' => $massCreatedModifications[$cv->code],
                            ]);
                        }
                    }
                }
            }
        }

        return true;
    }
}
