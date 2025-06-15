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


        // incomes
        // $revenues = Order
        //     ::where('status', Order::STATUS_COMPLETED)
        //     ->when($from, fn($q) => $q->where('created_at', '>=', $from))
        //     ->when($to, fn($q) => $q->where('created_at', '<=', $to))
        //     ->sum('total_amount');

        // additional consumption 
        // $production_costs = ProductionBatch
        //     ::where('status', 'completed')
        //     ->when($from, fn($q) => $q->where('completed_at', '>=', $from))
        //     ->when($to, fn($q) => $q->where('completed_at', '<=', $to))
        //     ->selectRaw('SUM(total_material_cost + additional_costs) as total')
        //     ->value('total');

        // $additionalVariantCosts = ProductVariant
        //     ::whereNull('deleted_at')
        //     ->sum('additional_cost');

        // $materials = ProductionBatchMaterial
        //     ::from('production_batches_materials as pbm')
        //     ->join('production_batches as pb', 'pbm.production_batch_id', '=', 'pb.id')
        //     ->leftJoin('products', function ($join) {
        //         $join->on('pbm.material_id', '=', 'products.id')
        //             ->where('pbm.material_type', '=', 'product');
        //     })
        //     ->leftJoin('product_variants', function ($join) {
        //         $join->on('pbm.material_id', '=', 'product_variants.id')
        //             ->where('pbm.material_type', '=', 'variant');
        //     })
        //     ->where('pb.status', 'completed')
        //     ->when($from, fn($q) => $q->where('pb.completed_at', '>=', $from))
        //     ->when($to, fn($q) => $q->where('pb.completed_at', '<=', $to))
        //     ->selectRaw("
        //     SUM(
        //         pbm.qty * 
        //         COALESCE(products.cost_price, product_variants.cost_price, 0)
        //     ) as total_cost
        // ")
        //     ->value('total_cost');

        // Profit calculation
        // $totalCosts = $production_costs;
        // + $materials
        // + $additionalVariantCosts;


        // $profit = $revenues - $production_costs;

        // return response()->json([
        //     'from' => $from,
        //     'to' => $to,
        //     'revenues' => round($revenues, 2),
        //     'production_costs' => round($production_costs, 2),
        //     // 'additional_costs' => round($additionalProductionCosts + $additionalVariantCosts, 2),
        //     // 'total_costs' => round($totalCosts, 2),
        //     'profit' => round($profit, 2),
        // ]);
    }

    // info learning
    public function financialSummaryOrders(Request $request)
    {
        $financialSummaryOrders = $this->moySkladReportService->financialSummaryOrders($request);

        return $financialSummaryOrders;
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

        return $product_profites;
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
}
