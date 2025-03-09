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
     *     path="/api/admin/client-levels",
     *     summary="Get all client levels",
     *     tags={"Client Levels"},
     *     @OA\Response(
     *         response=200,
     *         description="List of client levels",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ClientLevel"))
     *     )
     * )
     */
    public function index()
    {
        return response()->json(ClientLevel::all(), Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/client-levels",
     *     summary="Create a new client level",
     *     tags={"Client Levels"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "threshold", "calculation_type", "discount_percentage"},
     *             @OA\Property(property="name", type="string", example="Gold"),
     *             @OA\Property(property="threshold", type="number", example=5000),
     *             @OA\Property(property="calculation_type", type="string", enum={"order_count", "order_sum"}, example="order_sum"),
     *             @OA\Property(property="discount_percentage", type="number", example=10)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Client level created successfully")
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
     *     path="/api/admin/client-levels/{id}",
     *     summary="Update an existing client level",
     *     tags={"Client Levels"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ClientLevel")
     *     ),
     *     @OA\Response(response=200, description="Client level updated successfully")
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
     *     path="/api/admin/client-levels/{id}",
     *     summary="Delete a client level",
     *     tags={"Client Levels"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Client level deleted successfully")
     * )
     */
    public function destroy(ClientLevel $clientLevel)
    {
        $clientLevel->delete();
        return response()->json(['message' => 'Client level deleted successfully'], Response::HTTP_OK);
    }
}
