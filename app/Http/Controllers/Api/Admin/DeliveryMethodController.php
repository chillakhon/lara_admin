<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryMethodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/delivery/methods",
     *     operationId="getDeliveryMethods",
     *     tags={"Delivery Methods"},
     *     summary="Get a list of delivery methods with their associated zones, rates, and shipment count",
     *     description="Retrieve a list of delivery methods, including their associated zones, rates, and shipment count.",
     *     @OA\Response(
     *         response=200,
     *         description="List of delivery methods successfully retrieved",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/DeliveryMethod")
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid request parameters")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function index()
    {
        $methods = DeliveryMethod::with(['zones', 'rates'])
            ->withCount('shipments')
            ->get();

        return response()->json([
            'data' => $methods,
            'meta' => [
                'total_methods' => $methods->count(),
            ]
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/delivery/methods/{method}",
     *     summary="Get delivery method details",
     *     tags={"Delivery Methods"},
     *     @OA\Parameter(
     *         name="method",
     *         in="path",
     *         required=true,
     *         description="ID of the delivery method",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Details of the delivery method",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Standard Delivery"),
     *             @OA\Property(property="description", type="string", example="Delivery within 3-5 business days."),
     *             @OA\Property(
     *                 property="zones",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Zone A"),
     *                     @OA\Property(property="rates", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="price", type="float", example=15.50)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Delivery method not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Delivery method not found")
     *         )
     *     )
     * )
     */
    public function show(DeliveryMethod $method)
    {
        // Загружаем зоны и ставки для метода доставки
        $methodData = $method->load(['zones.rates']);

        // Возвращаем данные в формате JSON
        return response()->json($methodData);
    }

    /**
     * @OA\Post(
     *     path="/api/delivery/methods",
     *     operationId="storeDeliveryMethod",
     *     tags={"Delivery Methods"},
     *     summary="Create a new delivery method",
     *     description="Create a new delivery method with provided data.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code", "provider_class", "settings"},
     *             @OA\Property(property="name", type="string", description="Name of the delivery method", example="Express Delivery"),
     *             @OA\Property(property="code", type="string", description="Unique code for the delivery method", example="EXPRESS123"),
     *             @OA\Property(property="description", type="string", description="Optional description of the delivery method", example="Fast delivery service for urgent orders"),
     *             @OA\Property(property="provider_class", type="string", description="The class name of the delivery provider", example="App\\Delivery\\Providers\\ExpressProvider"),
     *             @OA\Property(property="settings", type="array", items=@OA\Items(type="string"), description="Settings for the delivery method", example={"max_weight", "delivery_time"}),
     *             @OA\Property(property="is_active", type="boolean", description="Whether the delivery method is active", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Delivery method created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Метод доставки создан"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/DeliveryMethod")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid input data")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:delivery_methods',
            'description' => 'nullable|string',
            'provider_class' => 'required|string',
            'settings' => 'required|array',
            'is_active' => 'boolean'
        ]);

        // Создаем новый метод доставки
        $deliveryMethod = DeliveryMethod::create($validated);

        // Возвращаем успешный JSON-ответ
        return response()->json([
            'message' => 'Метод доставки создан',
            'data' => $deliveryMethod
        ], 201); // Статус 201 для успешного создания ресурса
    }

    /**
     * @OA\Put(
     *     path="/api/delivery/methods/{method}",
     *     operationId="updateDeliveryMethod",
     *     tags={"Delivery Methods"},
     *     summary="Update an existing delivery method",
     *     description="Update the details of an existing delivery method with provided data.",
     *     @OA\Parameter(
     *         name="method",
     *         in="path",
     *         required=true,
     *         description="ID of the delivery method to be updated",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "provider_class", "settings"},
     *             @OA\Property(property="name", type="string", description="Name of the delivery method", example="Express Delivery"),
     *             @OA\Property(property="description", type="string", description="Optional description of the delivery method", example="Fast delivery service for urgent orders"),
     *             @OA\Property(property="provider_class", type="string", description="The class name of the delivery provider", example="App\\Delivery\\Providers\\ExpressProvider"),
     *             @OA\Property(property="settings", type="array", items=@OA\Items(type="string"), description="Settings for the delivery method", example={"max_weight", "delivery_time"}),
     *             @OA\Property(property="is_active", type="boolean", description="Whether the delivery method is active", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delivery method updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Метод доставки обновлен"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/DeliveryMethod")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid input data")
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
    public function update(Request $request, DeliveryMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider_class' => 'required|string',
            'settings' => 'required|array',
            'is_active' => 'boolean'
        ]);

        $method->update($validated);

        return response()->json([
            'message' => 'Метод доставки обновлен',
            'data' => $method
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/delivery/methods/{method}",
     *     operationId="deleteDeliveryMethod",
     *     tags={"Delivery Methods"},
     *     summary="Delete an existing delivery method",
     *     description="Delete the specified delivery method from the system.",
     *     @OA\Parameter(
     *         name="method",
     *         in="path",
     *         required=true,
     *         description="ID of the delivery method to be deleted",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delivery method deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Метод доставки удален")
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
    public function destroy(DeliveryMethod $method)
    {
        $method->delete();

        return response()->json([
            'message' => 'Метод доставки удален'
        ]);
    }

}
