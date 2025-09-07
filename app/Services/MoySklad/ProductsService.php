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

        $msProduct->name = $product?->name;
        $msProduct->code = "{$code}";// $product->slug ?? ($product->sku ?? null);
        $msProduct->description = $product->description ?? '';
        $msProduct->weight = $metrics['weight'];
        // $msProduct->volume = $metrics['volume'];
        $msProduct->salePrices = [
            [
                'value' => ($product->price ?? 0) * 100, // копейки
                'currency' => $defaultCurrency,
                'priceType' => $defaultPriceType[0],
            ],
        ];

        $msProduct->buyPrice = [
            'value' => ($product->cost_price ?? 0) * 100, // копейки
            'currency' => $defaultCurrency,
        ];

        $msProduct->uom = [
            "meta" => $foundUnit->meta,
        ];

        return $msProduct->create();
    }

    public function update_product(Product $product)
    {
        $moySkladHelperService = new MoySkladHelperService();
        $msProduct = \Evgeek\Moysklad\Api\Record\Objects\Entities\Product::make($this->moySklad);
        $msProduct->id = $product->uuid;

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


        // $code = rand(1000000000, 9999999999);

        $msProduct->name = $product?->name;
        // $msProduct->code = "{$code}";// $product->slug ?? ($product->sku ?? null);
        $msProduct->description = $product->description ?? '';
        $msProduct->weight = $metrics['weight'];
        // $msProduct->volume = $metrics['volume'];
        $msProduct->salePrices = [
            [
                'value' => ($product->price ?? 0) * 100, // копейки
                'currency' => $defaultCurrency,
                'priceType' => $defaultPriceType[0],
            ],
        ];

        $msProduct->buyPrice = [
            'value' => ($product->cost_price ?? 0) * 100, // копейки
            'currency' => $defaultCurrency,
        ];

        $msProduct->uom = [
            "meta" => $foundUnit->meta,
        ];

        try {
            $this->moySklad->query()->entity()->product()->byId($product->uuid)->get();
            return $msProduct->update();
        } catch (Exception $e) {
            return $msProduct->create();
        }
    }

    public function check_product_for_existence($uuid)
    {
        try {
            $product = $this->moySklad->query()->entity()->product()->byId($uuid);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete_product($id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->delete("{$this->baseURL}/entity/product/{$id}");

        if ($response->successful()) {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
            'message_type' => "MoySklad Error",
            'message' => json_decode($response->body())?->errors[0]?->error ?? "Ошибка удаления: невозможно удалить, так как продукт используется в других модулях.",
        ];
    }
}
