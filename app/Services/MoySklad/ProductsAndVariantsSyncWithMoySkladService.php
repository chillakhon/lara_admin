<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use Evgeek\Moysklad\MoySklad;
use Exception;
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

        $products = $moySkladService->get_products()->rows ?? [];
        $variants = $moySkladService->get_product_variants()->rows ?? [];

        // Index variants by product UUID
        $variantsGrouped = collect($variants)->groupBy(fn($v) => optional($v->product->meta)->href ?? '');

        foreach ($products as $productData) {
            $product = Product::updateOrCreate(
                ['uuid' => $productData->id],
                [
                    'name' => $productData->name ?? '',
                    'description' => $productData->description,
                    'slug' => Str::slug($productData->name ?? ''),
                    'price' => ($productData->salePrices[0]->value ?? 0) / 100,
                    'cost_price' => ($productData->buyPrice->value ?? 0) / 100,
                    'barcode' => $productData->barcodes[0]->ean13 ?? null,
                    'sku' => Str::slug($productData->name ?? ''),
                    'weight' => $productData->weight ?? 0,
                    'currency' => 'RUB',
                    'has_variants' => $productData->variantsCount > 0,
                ]
            );

            $productHref = $productData->meta->href;

            // Обновим/создадим варианты, если есть
            foreach ($variantsGrouped[$productHref] ?? [] as $variantData) {
                ProductVariant::updateOrCreate(
                    ['uuid' => $variantData->id],
                    [
                        'product_id' => $product->id,
                        'name' => $variantData->name ?? '',
                        'sku' => $variantData->code ?? '',
                        'barcode' => $variantData->barcodes[0]->ean13 ?? null,
                        'price' => ($variantData->salePrices[0]->value ?? 0) / 100,
                        'cost_price' => null,
                        'stock' => 0,
                        'weight' => $variantData->weight ?? 0,
                        'type' => 'simple',
                        'is_active' => true,
                    ]
                );
            }
        }

        return true;
    }
}
