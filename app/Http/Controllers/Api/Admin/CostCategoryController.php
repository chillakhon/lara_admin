<?php


namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CostCategoryResource;
use App\Models\CostCategory;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Get(
 *     path="/cost-categories",
 *     summary="Get list of active cost categories",
 *     tags={"Cost Categories"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/CostCategoryResource")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error"
 *     )
 * )
 */
class CostCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = CostCategory::where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json(CostCategoryResource::collection($categories));
    }
}
