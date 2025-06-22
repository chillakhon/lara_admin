<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => PromoCode::all()
        ]);
    }

    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0'
        ]);

        $promoCode = PromoCode::where('code', $request->code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->first();

        if (!$promoCode) {
            return response()->json([
                'message' => 'Промокод не найден или истек срок его действия'
            ], 404);
        }

        if ($promoCode->max_uses && $promoCode->total_uses >= $promoCode->max_uses) {
            return response()->json([
                'message' => 'Превышен лимит использований промокода'
            ], 400);
        }

        if ($promoCode->usages()->where('client_id', $request->client_id)->exists()) {
            return response()->json([
                'message' => 'Вы уже использовали этот промокод'
            ], 400);
        }

        return response()->json($promoCode);
    }
}
