<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\ClientLevel;

/**
 * @OA\Tag(name="Clients", description="API для управления клиентами")
 */
class ClientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/clients",
     *     operationId="getClients",
     *     tags={"Clients"},
     *     summary="Get all clients",
     *     @OA\Response(
     *         response=200,
     *         description="List of clients",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Client")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="prev", type="string"),
     *                 @OA\Property(property="next", type="string")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $clients = Client::with(['user.profile', 'level'])
            ->paginate(10);

        return response()->json($clients);
    }


    /**
     * @OA\Post(
     *     path="/clients",
     *     operationId="storeClient",
     *     tags={"Clients"},
     *     summary="Create a new client",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "password"},
     *             @OA\Property(property="first_name", type="string", maxLength=255, description="First name of the client"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, description="Last name of the client"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, description="Email address of the client"),
     *             @OA\Property(property="password", type="string", minLength=8, description="Password for the client account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client successfully created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Client successfully created"),
     *             @OA\Property(property="client", ref="#/components/schemas/Client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, validation failed"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $client = $user->client()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
        ]);

        return response()->json(['message' => 'Клиент успешно создан', 'client' => $client], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/clients/{client}",
     *     operationId="showClient",
     *     tags={"Clients"},
     *     summary="Get a specific client by ID",
     *     @OA\Parameter(
     *         name="client",
     *         in="path",
     *         required=true,
     *         description="ID of the client to fetch",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client data",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/Client"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found"
     *     )
     * )
     */
    public function show(Client $client)
    {
        return response()->json($client);
    }


    /**
     * @OA\Put(
     *     path="/clients/{client}",
     *     operationId="updateClient",
     *     tags={"Clients"},
     *     summary="Update a specific client",
     *     @OA\Parameter(
     *         name="client",
     *         in="path",
     *         required=true,
     *         description="ID of the client to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="first_name", type="string", maxLength=255),
     *             @OA\Property(property="last_name", type="string", maxLength=255),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client successfully updated",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/Client"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     )
     * )
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $client->user_id,
        ]);

        $client->update($validated);

        return response()->json(['message' => 'Клиент обновлен', 'client' => $client]);
    }


    /**
     * @OA\Delete(
     *     path="/clients/{client}",
     *     operationId="deleteClient",
     *     tags={"Clients"},
     *     summary="Delete a specific client",
     *     @OA\Parameter(
     *         name="client",
     *         in="path",
     *         required=true,
     *         description="ID of the client to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client successfully deleted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Client deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error deleting client"
     *     )
     * )
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Клиент удален']);
    }

}
