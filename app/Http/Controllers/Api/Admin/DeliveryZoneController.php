<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMethod;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryZoneController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/delivery/methods/{method}/zones",
     *     operationId="getDeliveryZones",
     *     tags={"Delivery Zones"},
     *     summary="Get a list of delivery zones for a specific delivery method",
     *     description="Retrieve the list of zones and rates associated with the specified delivery method.",
     *     @OA\Parameter(
     *         name="method",
     *         in="path",
     *         required=true,
     *         description="ID of the delivery method for which to retrieve zones",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of delivery zones retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/DeliveryZone")
     *             ),
     *             @OA\Property(property="method", type="object", ref="#/components/schemas/DeliveryMethod"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Delivery method not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Delivery method not found")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function index(DeliveryMethod $method)
    {
        $method->load('zones.rates'); // Загружаем зоны и тарифы

        return response()->json([
            'method' => $method,
            'data' => $method->zones,  // Возвращаем зоны
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/delivery/methods/{method}/zones",
     *     operationId="storeDeliveryZone",
     *     tags={"Delivery Zones"},
     *     summary="Create a new delivery zone for a specific delivery method",
     *     description="Create a new zone and associate it with the given delivery method.",
     *     @OA\Parameter(
     *         name="method",
     *         in="path",
     *         required=true,
     *         description="ID of the delivery method for which to create the zone",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "regions"},
     *             @OA\Property(property="name", type="string", description="Name of the zone"),
     *             @OA\Property(property="regions", type="array",
     *                 @OA\Items(type="string", description="Regions in the zone")
     *             ),
     *             @OA\Property(property="is_active", type="boolean", description="Zone status (active or not)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Zone created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Zone created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid input")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Delivery method not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Delivery method not found")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function store(Request $request, DeliveryMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'regions' => 'required|array',
            'regions.*' => 'string',
            'is_active' => 'boolean'
        ]);

        // Создаем новую зону для метода доставки
        $zone = $method->zones()->create($validated);

        return response()->json([
            'message' => 'Zone created successfully',
            'zone' => $zone
        ], 201); // Возвращаем ответ с успешным созданием
    }
    /**
     * @OA\Put(
     *     path="/api/delivery/zones/{zone}",
     *     operationId="updateDeliveryZone",
     *     tags={"Delivery Zones"},
     *     summary="Update an existing delivery zone",
     *     description="Update the details of an existing delivery zone.",
     *     @OA\Parameter(
     *         name="zone",
     *         in="path",
     *         required=true,
     *         description="ID of the zone to be updated",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "regions"},
     *             @OA\Property(property="name", type="string", description="Name of the zone"),
     *             @OA\Property(property="regions", type="array",
     *                 @OA\Items(type="string", description="Regions in the zone")
     *             ),
     *             @OA\Property(property="is_active", type="boolean", description="Zone status (active or not)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zone updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Zone updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid input")
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
    public function update(Request $request, DeliveryZone $zone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'regions' => 'required|array',
            'regions.*' => 'string',
            'is_active' => 'boolean'
        ]);

        // Обновляем данные зоны
        $zone->update($validated);

        return response()->json([
            'message' => 'Zone updated successfully',
            'zone' => $zone
        ], 200); // Возвращаем успешный ответ с обновленной зоной
    }
    /**
     * @OA\Delete(
     *     path="/api/delivery/zones/{zone}",
     *     operationId="deleteDeliveryZone",
     *     tags={"Delivery Zones"},
     *     summary="Delete an existing delivery zone",
     *     description="Delete a specific delivery zone.",
     *     @OA\Parameter(
     *         name="zone",
     *         in="path",
     *         required=true,
     *         description="ID of the zone to be deleted",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zone deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Zone deleted successfully")
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
    public function destroy(DeliveryZone $zone)
    {
        $zone->delete();

        return response()->json([
            'message' => 'Zone deleted successfully'
        ], 200); // Возвращаем успешный ответ с сообщением
    }

}
