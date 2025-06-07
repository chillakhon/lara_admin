<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ProductsTrait;
use Evgeek\Moysklad\Api\Record\Objects\UnknownObject;
use Evgeek\Moysklad\Formatters\ArrayFormat;
use Evgeek\Moysklad\MoySklad;
use Exception;
use Illuminate\Support\Facades\Http;
use Log;

class ProductsService
{

    use ProductsTrait;

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
    }

    public function create_product(Product $product)
    {
        $moySkladHelperService = new MoySkladHelperService();
        $msProduct = \Evgeek\Moysklad\Api\Record\Objects\Entities\Product::make($this->moySklad);

        $metrics = $this->calculateWeightAndVolume(
            $product->weight ?? 0,  // в граммах
            $product->length ?? 0,  // в сантиметрах
            $product->width ?? 0,
            $product->height ?? 0,
            $product->defaultUnit,
        );

        $defaultPriceType = $moySkladHelperService->get_price_types();
        if (empty($defaultPriceType)) {
            throw new Exception('Не удалось получить типы цен из МойСклад.');
        }

        $defaultCurrency = $moySkladHelperService->get_currencies();
        if (empty($defaultCurrency)) {
            throw new Exception('Не удалось получить валюту из МойСклад.');
        }

        $foundUnit = $moySkladHelperService->get_units($product->defaultUnit->name ?? null);
        if (!$foundUnit || empty($foundUnit->meta)) {
            throw new Exception("Не удалось найти единицу измерения '{$product->defaultUnit->name}' в МойСклад.");
        }


        $code = rand(1000000000, 9999999999);

        $msProduct->name = $product->name;
        $msProduct->code = "{$code}";// $product->slug ?? ($product->sku ?? null);
        $msProduct->description = $product->description ?? '';
        $msProduct->weight = $metrics['weight'];
        $msProduct->volume = $metrics['volume'];
        $msProduct->salePrices = [
            [
                'value' => ($product->price ?? 0) * 100, // копейки
                'currency' => $defaultCurrency,
                'priceType' => $defaultPriceType[0],
            ],
        ];

        Log::info("coming tuill here", [$metrics]);

        $msProduct->uom = [
            "meta" => $foundUnit->meta,
        ];

        return $msProduct->create();
    }

    public function update_product(Product $product)
    {
    }
}
