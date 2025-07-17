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

        if ($client instanceof \App\Models\User) {
            return response()->json([
                'success' => false,
                'message' => 'Клиент должен быть экземпляром модели Client, а не User.',
            ]);
        }

        $favorites = Favorite::with(['product', 'productVariant'])
            ->where('client_id', $client->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'is_favorite' => 'required|boolean',
        ]);

        $client = auth('sanctum')->user();

        if ($client instanceof \App\Models\User) {
            return response()->json([
                'success' => false,
                'message' => 'Клиент должен быть экземпляром модели Client, а не User.',
            ]);
        }

        $attributes = [
            'client_id' => $client->id,
            'product_id' => $request->product_id,
            'product_variant_id' => $request->product_variant_id,
        ];

        if ($request->is_favorite) {
            Favorite::firstOrCreate($attributes);
        } else {
            Favorite::where($attributes)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => $request->is_favorite ? 'Добавлено в избранное' : 'Удалено из избранного',
        ]);
    }
}
