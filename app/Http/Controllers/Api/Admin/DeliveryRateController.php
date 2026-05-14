<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\DeliveryRate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryRateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/delivery/zones/{zone}/rates",
     *     operationId="getDeliveryRates",
     *     tags={"Delivery Rates"},
     *     summary="Get the delivery rates for a specific zone",
     *     description="Retrieve a list of delivery rates associated with a given delivery zone.",
     *     @OA\Parameter(
     *         name="zone",
     *         in="path",
     *         required=true,
     *         description="ID of the zone to get rates for",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rates retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="zone", ref="#/components/schemas/DeliveryZone"),
     *             @OA\Property(property="rates", type="array",
     *                 @OA\Items(ref="#/components/schemas/DeliveryRate")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Zone not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Zone not found")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function index(DeliveryZone $zone)
    {
        // Загрузка тарифов для зоны и метода доставки
        $zone->load('rates');

        // Проверка существования зоны
        if (!$zone) {
            return response()->json([
                'error' => 'Zone not found'
            ], 404);
        }

        return response()->json([
            'zone' => $zone,
            'rates' => $zone->rates
        ], 200);
    }
    /**
     * @OA\Post(
     *     path="/api/delivery/zones/{zone}/rates",
     *     operationId="createDeliveryRate",
     *     tags={"Delivery Rates"},
     *     summary="Create a new delivery rate for a specific zone",
     *     description="Create a new delivery rate with specified weight, total amount, and rate for a given delivery zone.",
     *     @OA\Parameter(
     *         name="zone",
     *         in="path",
     *         required=true,
     *         description="ID of the zone to add a rate for",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "min_weight", "max_weight", "min_total", "max_total", "rate"},
     *             @OA\Property(property="name", type="string", description="Name of the rate"),
     *             @OA\Property(property="min_weight", type="number", format="float", description="Minimum weight for the rate"),
     *             @OA\Property(property="max_weight", type="number", format="float", description="Maximum weight for the rate"),
     *             @OA\Property(property="min_total", type="number", format="float", description="Minimum total for the rate"),
     *             @OA\Property(property="max_total", type="number", format="float", description="Maximum total for the rate"),
     *             @OA\Property(property="rate", type="number", format="float", description="The rate cost"),
     *             @OA\Property(property="is_active", type="boolean", description="Whether the rate is active or not")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Rate created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid data")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function store(Request $request, DeliveryZone $zone)
    {
        // Валидируем данные запроса
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'min_total' => 'required|numeric|min:0',
            'max_total' => 'required|numeric|gt:min_total',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        // Создаем новый тариф для зоны
        $zone->rates()->create($validated);

        // Возвращаем JSON-ответ с сообщением об успешном создании
        return response()->json([
            'message' => 'Rate created successfully',
            'rate' => $validated
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/delivery/rates/{rate}",
     *     operationId="updateDeliveryRate",
     *     tags={"Delivery Rates"},
     *     summary="Update an existing delivery rate",
     *     description="Update the details of an existing delivery rate for a specific zone.",
     *     @OA\Parameter(
     *         name="rate",
     *         in="path",
     *         required=true,
     *         description="ID of the delivery rate to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "min_weight", "max_weight", "min_total", "max_total", "rate"},
     *             @OA\Property(property="name", type="string", description="Name of the rate"),
     *             @OA\Property(property="min_weight", type="number", format="float", description="Minimum weight for the rate"),
     *             @OA\Property(property="max_weight", type="number", format="float", description="Maximum weight for the rate"),
     *             @OA\Property(property="min_total", type="number", format="float", description="Minimum total for the rate"),
     *             @OA\Property(property="max_total", type="number", format="float", description="Maximum total for the rate"),
     *             @OA\Property(property="rate", type="number", format="float", description="The rate cost"),
     *             @OA\Property(property="is_active", type="boolean", description="Whether the rate is active or not")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Rate updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid data")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */

    public function update(Request $request, DeliveryRate $rate)
    {
        // Валидируем входящие данные
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'min_total' => 'required|numeric|min:0',
            'max_total' => 'required|numeric|gt:min_total',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        // Обновляем тариф
        $rate->update($validated);

        // Возвращаем JSON-ответ с сообщением об успешном обновлении
        return response()->json([
            'message' => 'Rate updated successfully',
            'rate' => $rate
        ], 200);
    }
    /**
     * @OA\Delete(
     *     path="/api/delivery/rates/{rate}",
     *     operationId="destroyDeliveryRate",
     *     tags={"Delivery Rates"},
     *     summary="Delete an existing delivery rate",
     *     description="Delete a specific delivery rate from a zone.",
     *     @OA\Parameter(
     *         name="rate",
     *         in="path",
     *         required=true,
     *         description="ID of the delivery rate to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Rate deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Rate not found")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function destroy(DeliveryRate $rate)
    {
        // Удаляем тариф
        $rate->delete();

        // Возвращаем JSON-ответ с сообщением об успешном удалении
        return response()->json([
            'message' => 'Rate deleted successfully'
        ], 200);
    }

}
