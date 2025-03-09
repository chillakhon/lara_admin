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
     *     path="/api/admin/clients",
     *     summary="Получение списка клиентов",
     *     tags={"Clients"},
     *     @OA\Parameter(name="search", in="query", description="Поиск по имени или email", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Список клиентов")
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
     *     path="/api/admin/clients",
     *     summary="Создание клиента",
     *     tags={"Clients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "password"},
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *         )
     *     ),
     *     @OA\Response(response=201, description="Клиент успешно создан")
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
     *     path="/api/admin/clients/{id}",
     *     summary="Получение информации о клиенте",
     *     tags={"Clients"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Данные клиента")
     * )
     */
    public function show(Client $client)
    {
        return response()->json($client);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/clients/{id}",
     *     summary="Обновление данных клиента",
     *     tags={"Clients"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *         )
     *     ),
     *     @OA\Response(response=200, description="Клиент обновлен")
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
     *     path="/api/admin/clients/{id}",
     *     summary="Удаление клиента",
     *     tags={"Clients"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Клиент удален")
     * )
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Клиент удален']);
    }
}
