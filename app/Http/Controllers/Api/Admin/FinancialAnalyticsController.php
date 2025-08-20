<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\ProductVariant;
use App\Services\MoySklad\MoySkladHelperService;
use App\Services\MoySklad\ReportService;
use Carbon\Carbon;
use Evgeek\Moysklad\MoySklad;
use Http;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialAnalyticsController extends Controller
{

    private ReportService $moySkladReportService;

    public function __construct()
    {
        $this->moySkladReportService = new ReportService();
    }

    public function financialSummarySales(Request $request)
    {
        // $from = $request->input('from');
        // $to = $request->input('to');
        $financialSummaryOrders = $this->moySkladReportService->financialSummarySales($request);

        return response()->json($financialSummaryOrders);

    }

    // info learning
    public function financialSummaryOrders(Request $request)
    {
        $financialSummaryOrders = $this->moySkladReportService->financialSummaryOrders($request);

        return response()->json($financialSummaryOrders);
    }

    // info learning
    public function report_dashboard(Request $request)
    {
        $validate = $request->validate([
            'interval' => ['required', 'string', Rule::in(['day', 'week', 'month'])],
        ]);

        $indicators = $this->moySkladReportService->report_dashboard($validate['interval']);

        return $indicators;
    }

    public function income_by_products(Request $request)
    {
        $product_profites = $this->moySkladReportService->income_by_products($request);

        return response()->json($product_profites);
    }


    public function weeklyAmount(Request $request)
    {
        $from = $request->get('from')
            ? Carbon::parse($request->get('from'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $to = $request->get('to')
            ? Carbon::parse($request->get('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $clientId = $request->get('client_id');

        // null represents total orders
        $statuses = [null, 'completed', 'cancelled'];
        $weeks = collect();

        $currentStart = $from->copy();
        while ($currentStart < $to) {
            $currentEnd = $currentStart->copy()->addDays(6)->endOfDay();
            if ($currentEnd > $to) {
                $currentEnd = $to->copy();
            }

            $weekData = [
                'start_date' => $currentStart->toDateString(),
                'end_date' => $currentEnd->toDateString(),
                'total_by_status' => [],
            ];

            foreach ($statuses as $status) {
                $query = Order
                    ::whereBetween('created_at', [$currentStart, $currentEnd]);

                if ($status) {
                    $query->where('status', $status);
                }

                if ($clientId) {
                    $query->where('client_id', $clientId);
                }

                $sum = $query->sum('total_amount');

                $weekData['total_by_status'][$status ?? "orders"] = round($sum, 2);
            }

            $weeks->push($weekData);

            $currentStart = $currentEnd->copy()->addDay()->startOfDay();
        }

        return response()->json([
            'success' => true,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'filters' => [
                'client_id' => $clientId,
            ],
            'weeks' => $weeks
        ]);
    }


    public function combined_analytics(Request $request)
    {

        $reportService = new ReportService();

        $sales_summary = $reportService->financialSummarySales($request);

        $orders_summary = $reportService->financialSummaryOrders($request);

        $products_summary = $reportService->income_by_products($request);

        return response()->json([
            'success' => true,
            'sales_summary' => $sales_summary,
            "orders_summary" => $orders_summary,
            "products_summary" => $products_summary,
        ]);
    }
}
