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


    public function report_dashboard($type)
    {
        // https://api.moysklad.ru/api/remap/1.2/report/dashboard/day
        // or -> week, month
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->get("{$this->baseURL}/report/dashboard/" . $type);


        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => json_decode($response->body())
            ], $response->getStatusCode());
        }

        return json_decode($response->body());
    }

    public function income_by_products(Request $request)
    {
        try {
            $limit = 1000;
            $offset = 0;
            // $allProductsProfit = [];

            $totalProfit = 0;
            $totalRevenue = 0; // sellSum
            $totalCost = 0;    // sellCostSum

            do {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept-Encoding' => 'gzip',
                    'Content-Type' => 'application/json',
                ])->get("{$this->baseURL}/report/profit/byproduct", [
                            'momentFrom' => $request->get('from'),
                            'momentTo' => $request->get('to'),
                            'limit' => $limit,
                            'offset' => $offset,
                        ]);

                $data = $response->json();

                if (!isset($data['rows'])) {
                    break;
                }

                foreach ($data['rows'] as $item) {
                    $totalProfit += (float) ($item['profit'] ?? 0);
                    $totalRevenue += (float) ($item['sellSum'] ?? 0);
                    $totalCost += (float) ($item['sellCostSum'] ?? 0);
                    // $assortment = $item['assortment'] ?? [];
                    // $uom = $assortment['uom']['name'] ?? null;

                    // $allProductsProfit[] = [
                    //     'name' => $assortment['name'] ?? '',
                    //     'code' => $assortment['code'] ?? '',
                    //     'type' => $assortment['meta']['type'] ?? '', // product | service | bundle
                    //     'unit' => $uom,

                    //     'sell_quantity' => (float) ($item['sellQuantity'] ?? 0),
                    //     'sell_price' => (float) ($item['sellPrice'] ?? 0),
                    //     'sell_sum' => (float) ($item['sellSum'] ?? 0),
                    //     'sell_cost_sum' => (float) ($item['sellCostSum'] ?? 0),

                    //     'return_quantity' => (float) ($item['returnQuantity'] ?? 0),
                    //     'return_sum' => (float) ($item['returnSum'] ?? 0),
                    //     'return_cost_sum' => (float) ($item['returnCostSum'] ?? 0),

                    //     'profit' => (float) ($item['profit'] ?? 0),
                    //     'margin' => (float) ($item['margin'] ?? 0),
                    //     'sales_margin' => (float) ($item['salesMargin'] ?? 0),
                    // ];
                }

                $received = count($data['rows']);
                $offset += $limit;

            } while ($received === $limit);


            return response()->json([
                'success' => true,
                'total_profit' => $totalProfit,
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'line' => $e->getLine(),
                "message" => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }
}
