<?php

namespace App\Services\MoySklad;

use App\Http\Controllers\Api\Admin\MoySkladController;
use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Evgeek\Moysklad\MoySklad;
use Illuminate\Support\Str;
use Exception;

class ProductsAndVariantsSyncWithMoySkladService
{
    private MoySklad $moySklad;
    private string $token;
    private string $baseURL = "https://api.moysklad.ru/api/remap/1.2";

    public function __construct()
    {
        $settings = DeliveryServiceSetting::where('service_name', 'moysklad')->first();

        if (!$settings) {
            throw new Exception("Настройки для МойСклад не найдены. Пожалуйста, настройте сервис в админке.");
        }

        $this->token = $settings->token;
        $this->moySklad = new MoySklad([$this->token]);
    }

    public function sync_products_with_moysklad()
    {
        $helper = new MoySkladHelperService();
        $controller = new MoySkladController();

        $moyskladUnits = $this->getUnitsMap($helper);
        $products = $helper->get_products()->rows ?? [];
        $variants = $helper->get_product_variants()->rows ?? [];
        $stock = $helper->check_stock();

        $variantsGrouped = collect($variants)->groupBy(fn($v) => optional($v->product->meta)->href ?? '');

        $syncedUUIDs = [];

        foreach ($products as $productData) {
            $product = $this->upsertProduct($productData, $stock, $moyskladUnits);
            $syncedUUIDs[] = $productData->id;

            $this->syncVariantsForProduct($product, $productData, $variantsGrouped);
        }

        $this->removeDeletedProducts($syncedUUIDs);
        $this->syncLocalUnsyncedProducts($controller);

        return true;
    }

    private function getUnitsMap(MoySkladHelperService $service): array
    {
        $units = $service->get_units();
        return collect($units)->mapWithKeys(fn($unit) => [$unit->meta->href => $unit])->toArray();
    }

    private function findLocalUnit($msUnit): ?Unit
    {
        if (!$msUnit)
            return null;

        $msName = mb_strtolower($msUnit->name ?? '');
        $msDescription = mb_strtolower($msUnit->description ?? '');

        return Unit::where(function ($sql) use ($msName, $msDescription) {
            $sql->where('description', 'like', "%{$msDescription}%")
                ->orWhere('name', 'like', "%{$msName}%");
        })->first();
    }

    private function upsertProduct($data, array $stock, array $moyskladUnits): Product
    {
        $slug = Str::slug($data->name ?? '');
        $stockQty = $stock[$data->id]['stock'] ?? 0;
        $unit = $this->findLocalUnit($moyskladUnits[$data->uom->meta->href ?? null] ?? null);

        $product = Product::where('uuid', $data->id)->first()
            ?? Product::where('slug', $slug)->first();

        $attributes = [
            'uuid' => $data->id,
            'name' => $data->name ?? '',
            'description' => $data->description ?? null,
            'default_unit_id' => $unit?->id,
            'slug' => $slug,
            'price' => ($data->salePrices[0]->value ?? 0) / 100,
            'cost_price' => ($data->buyPrice->value ?? 0) / 100,
            'barcode' => $data->barcodes[0]->ean13 ?? null,
            'stock_quantity' => $stockQty,
            'sku' => $slug,
            'weight' => $data->weight ?? 0,
            'currency' => 'RUB',
            'has_variants' => $data->variantsCount > 0,
        ];

        return $product ? tap($product)->update($attributes) : Product::create($attributes);
    }

    private function syncVariantsForProduct(Product $product, $productData, $variantsGrouped): void
    {
        $productHref = $productData->meta->href;
        $variantDataList = $variantsGrouped[$productHref] ?? [];

        foreach ($variantDataList as $variantData) {
            $this->upsertVariant($product, $variantData, $productData);
        }
    }

    private function upsertVariant(Product $product, $data, $productData): void
    {
        $slug = Str::slug($data->name ?? '');
        $variant = ProductVariant::where('uuid', $data->id)->first()
            ?? ProductVariant::where('sku', $slug)->where('product_id', $product->id)->first();

        $attributes = [
            'uuid' => $data->id,
            'product_id' => $product->id,
            'name' => $data->name ?? '',
            'unit_id' => $product->default_unit_id,
            'sku' => $slug,
            'barcode' => $data->barcodes[0]->ean13 ?? null,
            'price' => ($data->salePrices[0]->value ?? 0) / 100,
            'cost_price' => null,
            'stock' => $data->stock ?? 0,
            'weight' => $productData->weight ?? 0,
            'type' => 'simple',
            'is_active' => true,
        ];

        $variant ? $variant->update($attributes) : ProductVariant::create($attributes);
    }

    private function removeDeletedProducts(array $syncedUUIDs): void
    {
        Product::whereNotNull('uuid')->whereNotIn('uuid', $syncedUUIDs)->delete();
    }

    private function syncLocalUnsyncedProducts(MoySkladController $controller): void
    {
        $unsynced = Product::whereNull('uuid')->get();

        foreach ($unsynced as $product) {
            $msProduct = $controller->check_product_for_existence($product->uuid)
                ? $controller->update_product($product)
                : $controller->create_product($product);

            if ($msProduct) {
                $product->update(['uuid' => $msProduct->id]);

                $variants = ProductVariant::where('product_id', $product->id)->get();

                foreach ($variants as $variant) {
                    if (!$variant->code) {
                        $variant->update([
                            'code' => (string) rand(1000000000, 9999999999),
                        ]);
                    }
                }

                if ($variants->count() > 0) {
                    $remoteVariants = $controller->mass_variant_creation_and_update($variants, $msProduct);

                    foreach ($variants as $variant) {
                        if (isset($remoteVariants[$variant->code])) {
                            $variant->update([
                                'uuid' => $remoteVariants[$variant->code],
                            ]);
                        }
                    }
                }
            }
        }
    }
}