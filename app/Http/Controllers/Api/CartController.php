<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Client;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function get(Request $request)
    {
        dd($request);
    }

    public function store(Request $request)
    {
        $client = Client::where('tg_id', '=', $request->id)->whereNull('deleted_at')->first();
        Cart::create(
            [
                'client_id' => $client->id,
                'products' => json_encode($request->products),
            ]
        );
    }

    public function update(Request $request)
    {

        $client = Client::where('tg_id', $request->id)->whereNull('deleted_at')->first();

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        if (!$client->cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $client->cart->update([
            'client_id' => $client->id,
            'products' => json_encode($request->data),
        ]);

        return response()->json(['success' => 'Cart updated successfully']);
    }
}
