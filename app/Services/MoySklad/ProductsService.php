<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Traits\ProductsTrait;
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

    public function get_currencies()
    {
        return $this->moySklad->query()->entity()->currency()->get()->rows[0];
    }

    public function get_price_types()
    {
        return $this->moySklad->query()->context()->companysettings()->pricetype()->get();
    }

    public function get_units()
    {
        return $this->moySklad->query()->entity()->uom()->get();
    }

    public function check_products()
    {
        return $this->moySklad->query()->entity()->product()->get();
    }

    public function check_stock()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->get("{$this->baseURL}/report/stock/all/current");

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => $response->body(),
            ], $response->getStatusCode());
        }

        return $response->json();
    }


    public function sync_products_with_moysklad()
    {
    }

    public function create_product(Product $product)
    {
        $msProduct = \Evgeek\Moysklad\Api\Record\Objects\Entities\Product::make($this->moySklad);
        $metrics = $this->calculateWeightAndVolume($product);


        $defaultPriceType = $this->get_price_types();
        $defaultCurrency = $this->get_currencies();

        $msProduct->name = $product->name;
        $msProduct->code = $product->slug ?? ($product->sku ?? null);
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

        $msProduct->uom = [
            "meta" => json_decode($product->defaultUnit->meta_data),
        ];

        $msProduct->create();
    }

    public function update_product(Product $product)
    {
    }
}
