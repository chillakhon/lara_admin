<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class FinancialAnalyticsController extends Controller
{
    public function financialSummary(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        // incomes
        $revenues = Order
            ::where('status', Order::STATUS_COMPLETED)
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->sum('total_amount');

        // additional consumption 
        $production_costs = ProductionBatch
            ::where('status', 'completed')
            ->when($from, fn($q) => $q->where('completed_at', '>=', $from))
            ->when($to, fn($q) => $q->where('completed_at', '<=', $to))
            ->selectRaw('SUM(total_material_cost + additional_costs) as total')
            ->value('total');

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


        $profit = $revenues - $production_costs;

        return response()->json([
            'from' => $from,
            'to' => $to,
            'revenues' => round($revenues, 2),
            'production_costs' => round($production_costs, 2),
            // 'additional_costs' => round($additionalProductionCosts + $additionalVariantCosts, 2),
            // 'total_costs' => round($totalCosts, 2),
            'profit' => round($profit, 2),
        ]);
    }
}
