<?php

namespace App\Services\MoySklad;

use App\Http\Controllers\Api\Admin\MoySkladController;
use App\Models\Color;
use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Evgeek\Moysklad\MoySklad;
use Illuminate\Support\Str;
use Exception;
use Laravel\Reverb\Loggers\Log;

class ProductsAndVariantsSyncWithMoySkladService
{
    private MoySklad $moySklad;
    private string $token;
    private string $baseURL = "https://api.moysklad.ru/api/remap/1.2";

    // Константы для ограничений значений
    private const MAX_WEIGHT = 99999.99; // Максимальный вес в кг
    private const MAX_PRICE = 999999999.99; // Максимальная цена
    private const MAX_STOCK = 2147483647; // Максимальное количество для INT
    private const MIN_VALUE = 0; // Минимальное значение

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
            try {
                $product = $this->upsertProduct($productData, $stock, $moyskladUnits);
                $syncedUUIDs[] = $productData->id;

                $this->syncVariantsForProduct($product, $stock, $productData, $variantsGrouped);
            } catch (Exception $e) {
                // Логирование ошибки для конкретного продукта
                \Log::error("Ошибка синхронизации продукта {$productData->id}: " . $e->getMessage());
                continue; // Продолжаем обработку других продуктов
            }
        }

        $this->removeDeletedProducts($syncedUUIDs);
        $this->syncLocalUnsyncedProducts($controller);
    }

    private function getUnitsMap(MoySkladHelperService $service): array
    {
        $units = $service->get_units();
        return collect($units)->mapWithKeys(fn($unit) => [$unit->meta->href => $unit])->toArray();
    }

    private function findLocalUnit($msUnit): ?Unit
    {
        if (!$msUnit) {
            return null;
        }

        $msName = mb_strtolower($msUnit->name ?? '');
        $msDescription = mb_strtolower($msUnit->description ?? '');

        return Unit::where(function ($sql) use ($msName, $msDescription) {
            $sql->where('description', 'like', "%{$msDescription}%")
                ->orWhere('name', 'like', "%{$msName}%");
        })->first();
    }

    /**
     * Нормализует числовое значение в допустимых пределах
     */
    private function normalizeNumericValue($value, $max = null, $min = self::MIN_VALUE, $decimals = 2): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $numericValue = is_numeric($value) ? (float)$value : 0.0;

        if ($max !== null && $numericValue > $max) {
            $numericValue = $max;
        }

        if ($numericValue < $min) {
            $numericValue = $min;
        }

        return round($numericValue, $decimals);
    }

    /**
     * Нормализует целое число в допустимых пределах
     */
    private function normalizeIntValue($value, $max = self::MAX_STOCK, $min = self::MIN_VALUE): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $intValue = is_numeric($value) ? (int)$value : 0;

        if ($intValue > $max) {
            $intValue = $max;
        }

        if ($intValue < $min) {
            $intValue = $min;
        }

        return $intValue;
    }

    /**
     * Безопасно извлекает и нормализует вес
     */
    private function extractWeight($data): float
    {
        $weight = $data->weight ?? 0;

        // Если вес в граммах, конвертируем в килограммы
        if ($weight > 1000) {
            $weight = $weight / 1000;
        }

        return $this->normalizeNumericValue($weight, self::MAX_WEIGHT);
    }

    /**
     * Безопасно извлекает цену
     */
    private function extractPrice($priceData): float
    {
        if (empty($priceData) || !is_array($priceData)) {
            return 0.0;
        }

        $price = ($priceData[0]->value ?? 0) / 100;
        return $this->normalizeNumericValue($price, self::MAX_PRICE);
    }

    /**
     * Безопасно извлекает стоимость
     */
    private function extractCostPrice($buyPrice): float
    {
        $cost = ($buyPrice->value ?? 0) / 100;
        return $this->normalizeNumericValue($cost, self::MAX_PRICE);
    }

    private function upsertProduct($data, array $stock, array $moyskladUnits): Product
    {
        $slug = Str::slug($data->id ?? '');
        $stockQty = $this->normalizeIntValue($stock[$data->id]['stock'] ?? 0);
        $unit = $this->findLocalUnit($moyskladUnits[$data->uom->meta->href ?? null] ?? null);

        // Сначала ищем по UUID, потом по slug
        $product = Product::where('uuid', $data->id)->first();

        if (!$product) {
            $product = Product::where('slug', $slug)->first();
        }

        $attributes = [
            'uuid' => $data->id,
            'name' => mb_substr($data->name ?? '', 0, 255), // Ограничиваем длину
            'description' => $data->description ?? null,
            'default_unit_id' => $unit?->id,
            'slug' => $slug,
            'price' => $this->extractPrice($data->salePrices ?? []),
            'cost_price' => $this->extractCostPrice($data->buyPrice ?? (object)['value' => 0]),
            'barcode' => $this->extractBarcode($data),
            'code' => $data->code ?? null, // Сохраняем код точно как в МойСклад
            'stock_quantity' => $stockQty,
//            'sku' => $slug,
            'weight' => $this->extractWeight($data),
            'currency' => 'RUB',
            'has_variants' => ($data->variantsCount ?? 0) > 0,
        ];

        if ($product) {
            $product->update($attributes);
            return $product;
        }

        return Product::create($attributes);
    }

    private function extractBarcode($data): ?string
    {
        if (!empty($data->barcodes) && is_array($data->barcodes)) {
            $first = $data->barcodes[0];
            // Сохраняем баркод точно как в МойСклад
            return $first->ean13
                ?? $first->ean8
                ?? $first->code128
                ?? $first->gtin
                ?? null;
        }
        return null;
    }

    private function syncVariantsForProduct(Product $product, array $stock, $productData, $variantsGrouped): void
    {
        $productHref = $productData->meta->href;
        $variantDataList = $variantsGrouped[$productHref] ?? [];
        $updateCreatedVariantsIds = [];

        foreach ($variantDataList as $variantData) {
            try {
                $variantLocal = $this->upsertVariant($product, $stock, $variantData, $productData);
                $updateCreatedVariantsIds[] = $variantLocal->id;
            } catch (Exception $e) {
                \Log::error("Ошибка синхронизации варианта продукта {$product->id}: " . $e->getMessage());
                continue;
            }
        }

        // Удаляем варианты, которых больше нет в МойСклад
        ProductVariant::where('product_id', $product->id)
            ->whereNotIn('id', $updateCreatedVariantsIds)
            ->each(function ($variant) {
                $variant->update(['sku' => null]);
                $variant->delete();
            });
    }

    private function upsertVariant(Product $product, array $stock, $data, $productData)
    {


        try {
            $characteristic = collect($data->characteristics ?? [])
                ->firstWhere('name', 'Размер');
            $colorCharacteristic = collect($data->characteristics ?? [])
                ->firstWhere('name', 'Цвет');

            $color_name = $colorCharacteristic?->value ?? '';
            $findColorFromTable = Color::where(function ($sql) use ($color_name) {
                $sql->where('name', 'like', "%{$color_name}%")
                    ->orWhere('normalized_name', 'like', "%{$color_name}%");
            })->first();

            $variant_name = mb_substr($characteristic?->value ?? '', 0, 255);
            $slug = Str::slug($variant_name);
            $sku = $slug . '-' . $product->id;

            // Сначала ищем по UUID, потом по SKU
            $variant = ProductVariant::withTrashed()
                ->where('uuid', $data->id)
                ->first();

            if (!$variant) {
                $variant = ProductVariant::withTrashed()
                    ->where('sku', $sku)
                    ->where('product_id', $product->id)
                    ->first();
            }


            \Illuminate\Support\Facades\Log::info('test', [
//                'product_id' => $product->id,
//                'variant_name' => $variant_name,
//                'variant_sku' => $variant->sku,
            ]);

            $stockQty = $this->normalizeIntValue($stock[$data->id]['stock'] ?? 0);

            // Безопасное извлечение баркода из варианта (сохраняем точно как в МойСклад)
            $variantBarcode = null;
            if (!empty($data->barcodes) && is_array($data->barcodes)) {
                $variantBarcode = $data->barcodes[0]->ean13 ?? null;
            }

            $attributes = [
                'uuid' => $data->id,
                'product_id' => $product->id,
                'color_id' => $findColorFromTable?->id,
                'name' => $variant_name,
                'unit_id' => $product->default_unit_id,
//                'sku' => $sku,
                'barcode' => $variantBarcode,
                'code' => $data->code ?? null, // Сохраняем код точно как в МойСклад
                'price' => $this->extractPrice($data->salePrices ?? []),
                'cost_price' => $this->extractCostPrice($data->buyPrice ?? (object)['value' => 0]),
                'stock' => $stockQty,
                'weight' => $this->extractWeight($productData),
                'type' => 'simple',
                'is_active' => true,
                'deleted_at' => null,
            ];

            if ($variant) {

//                ProductVariant::withTrashed()->where('id', $variant->id)->restore();
                unset($attributes['uuid']);
                $variant->update($attributes);

                return $variant;
            }

            return ProductVariant::create($attributes);

        } catch (Exception $e) {

            \Illuminate\Support\Facades\Log::error('catch', [
                'getLine' => $e->getLine(),
                'getTraceAsString' => $e->getTraceAsString(),
                'getMessage' => $e->getMessage(),
            ]);


        }

    }

    private function removeDeletedProducts(array $syncedUUIDs): void
    {
        Product::whereNotNull('uuid')
            ->whereNotIn('uuid', $syncedUUIDs)
            ->delete();
    }

    private function syncLocalUnsyncedProducts(MoySkladController $controller): void
    {
        $unsynced = Product::whereNull('uuid')->get();

        foreach ($unsynced as $product) {
            try {
                $msProduct = $controller->check_product_for_existence($product->uuid)
                    ? $controller->update_product($product)
                    : $controller->create_product($product);

                if ($msProduct) {
                    $product->update(['uuid' => $msProduct->id]);

                    $variants = ProductVariant::where('product_id', $product->id)->get();

                    foreach ($variants as $variant) {
                        if (!$variant->code) {
                            $variant->update([
                                'code' => (string)rand(1000000000, 9999999999),
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
            } catch (Exception $e) {
                \Log::error("Ошибка синхронизации локального продукта {$product->id}: " . $e->getMessage());
                continue;
            }
        }
    }
}
