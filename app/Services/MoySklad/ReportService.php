<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use Carbon\Carbon;
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

                }

                $received = count($data['rows']);
                $offset += $limit;

            } while ($received === $limit);


            return [
                'total_profit' => $totalProfit,
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
            ];
        } catch (Exception $e) {
            return [
                'line' => $e->getLine(),
                "message" => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ];
        }
    }


    public function financialSummarySales(Request $request)
    {

        $dateFrom = $request->get('from')
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->get('from'))
            : now()->startOfDay();

        $dateTo = $request->get('to')
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->get('to'))
            : now()->endOfDay();

        $diffInDays = $dateFrom->diffInDays($dateTo);

        if (in_array($request->get('interval'), ['hour', 'day', 'month'])) {
            $interval = $request->get('interval');
        }

        $interval = match (true) {
            $diffInDays <= 1 => 'hour',
            // $diffInDays <= 7 => 'day',
            $diffInDays <= 31 => 'day',
            default => 'month',
        };

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->get('https://api.moysklad.ru/api/remap/1.2/report/sales/plotseries', [
                    'momentFrom' => $dateFrom->format('Y-m-d H:i:s'),
                    'momentTo' => $dateTo->format('Y-m-d H:i:s'),
                    'interval' => $interval,
                ]);

        $data = $response->json();

        // Calculate totals from series
        $totalQuantity = 0;
        $totalSum = 0;
        $series = [];

        foreach ($data['series'] ?? [] as $item) {
            $totalQuantity += $item['quantity'];
            $totalSum += $item['sum'];

            $series[] = [
                // Берем только дату (без времени)
                'date' => Carbon::parse($item['date'])->format('Y-m-d'),
                'quantity' => $item['quantity'],
                'sum' => $item['sum'],
            ];
        }


        return [
            'total_quantity' => $totalQuantity,
            'total_sum' => $totalSum,
            'series' => $series,
        ];
    }

    public function financialSummaryOrders(Request $request)
    {
        $dateFrom = $request->get('from')
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->get('from'))
            : now()->startOfDay();

        $dateTo = $request->get('to')
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->get('to'))
            : now()->endOfDay();

        $diffInDays = $dateFrom->diffInDays($dateTo);

        $interval = in_array($request->get('interval'), ['hour', 'day', 'month'])
            ? $request->get('interval')
            : match (true) {
                $diffInDays <= 1 => 'hour',
                $diffInDays <= 31 => 'day',
                default => 'month',
            };

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->get('https://api.moysklad.ru/api/remap/1.2/report/orders/plotseries', [
                    'momentFrom' => $dateFrom->format('Y-m-d H:i:s'),
                    'momentTo' => $dateTo->format('Y-m-d H:i:s'),
                    'interval' => $interval,
                ]);

        $data = $response->json();

        $totalQuantity = 0;
        $totalSum = 0;
        $series = [];

        foreach ($data['series'] ?? [] as $item) {
            $totalQuantity += $item['quantity'];
            $totalSum += $item['sum'];
            $series[] = [
                'date' => Carbon::parse($item['date'])->format('Y-m-d'),
                'quantity' => $item['quantity'],
                'sum' => $item['sum'],
            ];
        }

        return [
            'total_quantity' => $totalQuantity,
            'total_sum' => $totalSum,
            'series' => $series,
        ];
    }
}
