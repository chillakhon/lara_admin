<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use Evgeek\Moysklad\MoySklad;
use Exception;
use Http;
use Illuminate\Http\Request;

class ReportService
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


    public function report_dashboard(Request $request)
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->get('https://api.moysklad.ru/api/remap/1.2/report/dashboard/day');


        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => json_decode($response->body())
            ], $response->getStatusCode());
        }

        return json_decode($response->body());
    }
}
