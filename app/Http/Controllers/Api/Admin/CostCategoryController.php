<?php


namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CostCategoryResource;
use App\Models\CostCategory;
use Illuminate\Http\JsonResponse;


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
