<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\JsonResponse;

class ColorController extends Controller
{
    public function index(): JsonResponse
    {
        $colors = Color::select('id', 'name', 'code')
            ->get();

        return response()->json([
            'success' => true,
            'colors' => $colors,
        ]);
    }

    public function get_colors(): JsonResponse
    {
        $colors = Color::whereHas('productVariants')
            ->select('id', 'name', 'code')
            ->get();

        return response()->json([
            'success' => true,
            'colors' => $colors,
        ]);
    }

}
