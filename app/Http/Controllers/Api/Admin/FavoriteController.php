<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function favorites(Request $request)
    {
        $client = auth('sanctum')->user();

        $favorites = Favorite::with(['product', 'productVariant'])
            ->where('client_id', $client->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }
}
