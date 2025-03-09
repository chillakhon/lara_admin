<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Client Levels", description="API for managing client levels")
 */
class ClientLevelController extends Controller
{
    /**
     * @OA\Get(
     *     path="/client-levels",
     *     operationId="getClientLevels",
     *     tags={"ClientLevels"},
     *     summary="Get all client levels",
     *     description="Returns a list of all client levels",
     *     @OA\Response(
     *         response=200,
     *         description="List of client levels",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/ClientLevel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request"
     *     )
     * )
     */
    public function index()
    {
        return response()->json(ClientLevel::all(), Response::HTTP_OK);
    }


    /**
     * @OA\Post(
     *     path="/client-levels",
     *     operationId="createClientLevel",
     *     tags={"ClientLevels"},
     *     summary="Create a new client level",
     *     description="Creates a new client level based on the provided data",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "threshold", "calculation_type", "discount_percentage"},
     *             @OA\Property(property="name", type="string", description="Name of the client level"),
     *             @OA\Property(property="threshold", type="number", format="float", description="Threshold for the client level"),
     *             @OA\Property(property="calculation_type", type="string", enum={"order_count", "order_sum"}, description="Type of calculation for the client level"),
     *             @OA\Property(property="discount_percentage", type="number", format="float", description="Discount percentage for the client level")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client level successfully created",
     *         @OA\JsonContent(ref="#/components/schemas/ClientLevel")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'threshold' => 'required|numeric|min:0',
            'calculation_type' => 'required|in:order_count,order_sum',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $level = ClientLevel::create($validated);

        return response()->json($level, Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *     path="/client-levels/{clientLevel}",
     *     operationId="updateClientLevel",
     *     tags={"ClientLevels"},
     *     summary="Update a client level",
     *     description="Updates an existing client level based on the provided data",
     *     @OA\Parameter(
     *         name="clientLevel",
     *         in="path",
     *         required=true,
     *         description="ID of the client level to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "threshold", "calculation_type", "discount_percentage"},
     *             @OA\Property(property="name", type="string", description="Name of the client level"),
     *             @OA\Property(property="threshold", type="number", format="float", description="Threshold for the client level"),
     *             @OA\Property(property="calculation_type", type="string", enum={"order_count", "order_sum"}, description="Type of calculation for the client level"),
     *             @OA\Property(property="discount_percentage", type="number", format="float", description="Discount percentage for the client level")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client level successfully updated",
     *         @OA\JsonContent(ref="#/components/schemas/ClientLevel")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data"
     *     )
     * )
     */
    public function update(Request $request, ClientLevel $clientLevel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'threshold' => 'required|numeric|min:0',
            'calculation_type' => 'required|in:order_count,order_sum',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $clientLevel->update($validated);

        return response()->json($clientLevel, Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/client-levels/{clientLevel}",
     *     operationId="deleteClientLevel",
     *     tags={"ClientLevels"},
     *     summary="Delete a client level",
     *     description="Deletes a client level by its ID",
     *     @OA\Parameter(
     *         name="clientLevel",
     *         in="path",
     *         required=true,
     *         description="ID of the client level to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client level successfully deleted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Client level deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client level not found"
     *     )
     * )
     */
    public function destroy(ClientLevel $clientLevel)
    {
        $clientLevel->delete();
        return response()->json(['message' => 'Client level deleted successfully'], Response::HTTP_OK);
    }

}
