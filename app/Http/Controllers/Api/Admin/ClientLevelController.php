<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;


class ClientLevelController extends Controller
{
    public function index()
    {
        return response()->json(ClientLevel::all(), Response::HTTP_OK);
    }


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


    public function destroy(ClientLevel $clientLevel)
    {
        $clientLevel->delete();
        return response()->json(['message' => 'Client level deleted successfully'], Response::HTTP_OK);
    }

}
