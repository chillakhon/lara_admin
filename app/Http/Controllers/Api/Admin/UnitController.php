<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;

class UnitController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/units",
     *     summary="Получить все единицы",
     *     description="Получает все единицы из базы данных",
     *     operationId="getAllUnits",
     *     tags={"Units"},
     *     @OA\Response(
     *         response=200,
     *         description="Список всех единиц",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Unit")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Server error")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $units = Unit::all();

        return response()->json($units);
    }
}
