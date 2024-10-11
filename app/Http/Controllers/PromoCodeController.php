<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromoCodeController extends Controller
{
    public function index()
    {
        $promoCodes = PromoCode::all();
        return Inertia::render('Dashboard/PromoCodes/Index', ['promoCodes' => $promoCodes]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:promo_codes,code',
            'discount_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percentage,fixed',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        PromoCode::create($validated);

        return redirect()->back()->with('success', 'Promo code created successfully.');
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $validated = $request->validate([
            'code' => 'required|unique:promo_codes,code,' . $promoCode->id,
            'discount_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percentage,fixed',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $promoCode->update($validated);

        return redirect()->back()->with('success', 'Promo code updated successfully.');
    }

    public function destroy(PromoCode $promoCode)
    {
        $promoCode->delete();

        return redirect()->back()->with('success', 'Promo code deleted successfully.');
    }

    public function usage(PromoCode $promoCode)
    {
        $usages = $promoCode->usages()->with(['order', 'client'])->get();

        return response()->json([
            'code' => $promoCode->code,
            'total_uses' => $usages->count(),
            'usages' => $usages->map(function ($usage) {
                return [
                    'id' => $usage->id,
                    'order' => [
                        'id' => $usage->order->id,
                        'order_number' => $usage->order->order_number,
                    ],
                    'client' => [
                        'id' => $usage->client->id,
                        'name' => $usage->client->getDisplayName(),
                    ],
                    'discount_amount' => $usage->discount_amount,
                    'created_at' => $usage->created_at,
                ];
            }),
        ]);
    }
}

