<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use Evgeek\Moysklad\Formatters\ArrayFormat;
use Evgeek\Moysklad\MoySklad;
use Exception;
use Illuminate\Support\Facades\Http;

class ProductsService
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
}
