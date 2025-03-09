<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromoCodeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/promo-codes",
     *     summary="Получить список промокодов",
     *     tags={"Promo Codes"},
     *     @OA\Response(
     *         response=200,
     *         description="Список промокодов",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="DISCOUNT10"),
     *                 @OA\Property(property="discount", type="number", example=10),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2025-12-31T23:59:59Z")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json([
            'promoCodes' => PromoCode::all(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/promo-codes",
     *     summary="Создать промокод",
     *     tags={"Promo Codes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "discount_amount", "discount_type"},
     *             @OA\Property(property="code", type="string", example="DISCOUNT20"),
     *             @OA\Property(property="discount_amount", type="number", example=20),
     *             @OA\Property(property="discount_type", type="string", enum={"percentage", "fixed"}, example="percentage"),
     *             @OA\Property(property="starts_at", type="string", format="date", example="2025-01-01"),
     *             @OA\Property(property="expires_at", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(property="max_uses", type="integer", example=100),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Промокод успешно создан",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Promo code created successfully."),
     *             @OA\Property(property="promo_code", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="DISCOUNT20"),
     *                 @OA\Property(property="discount_amount", type="number", example=20),
     *                 @OA\Property(property="discount_type", type="string", example="percentage"),
     *                 @OA\Property(property="starts_at", type="string", format="date", example="2025-01-01"),
     *                 @OA\Property(property="expires_at", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="max_uses", type="integer", example=100),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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

        $promoCode = PromoCode::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Promo code created successfully.',
            'promo_code' => $promoCode
        ], 201);
    }


    /**
     * @OA\Put(
     *     path="/api/promo-codes/{id}",
     *     summary="Обновить промокод",
     *     tags={"Promo Codes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID промокода",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "discount_amount", "discount_type"},
     *             @OA\Property(property="code", type="string", example="DISCOUNT30"),
     *             @OA\Property(property="discount_amount", type="number", example=30),
     *             @OA\Property(property="discount_type", type="string", enum={"percentage", "fixed"}, example="fixed"),
     *             @OA\Property(property="starts_at", type="string", format="date", example="2025-02-01"),
     *             @OA\Property(property="expires_at", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(property="max_uses", type="integer", example=150),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Промокод успешно обновлен",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Promo code updated successfully."),
     *             @OA\Property(property="promo_code", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="DISCOUNT30"),
     *                 @OA\Property(property="discount_amount", type="number", example=30),
     *                 @OA\Property(property="discount_type", type="string", example="fixed"),
     *                 @OA\Property(property="starts_at", type="string", format="date", example="2025-02-01"),
     *                 @OA\Property(property="expires_at", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="max_uses", type="integer", example=150),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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

        return response()->json([
            'success' => true,
            'message' => 'Promo code updated successfully.',
            'promo_code' => $promoCode
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/promo-codes/{id}",
     *     summary="Удалить промокод",
     *     tags={"Promo Codes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID промокода",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Промокод успешно удален",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Promo code deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Промокод не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Promo code not found.")
     *         )
     *     )
     * )
     */
    public function destroy(PromoCode $promoCode)
    {
        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Promo code not found.'
            ], 404);
        }

        $promoCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo code deleted successfully.'
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/promo-codes/{id}/usage",
     *     summary="Получить статистику использования промокода",
     *     tags={"Promo Codes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID промокода",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Детальная информация об использовании промокода",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="code", type="string", example="DISCOUNT2024"),
     *             @OA\Property(property="total_uses", type="integer", example=5),
     *             @OA\Property(
     *                 property="usages",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(
     *                         property="order",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1001),
     *                         @OA\Property(property="order_number", type="string", example="ORD-20240308-XYZ")
     *                     ),
     *                     @OA\Property(
     *                         property="client",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=50),
     *                         @OA\Property(property="name", type="string", example="Иван Иванов")
     *                     ),
     *                     @OA\Property(property="discount_amount", type="number", format="float", example=500.00),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-08T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Промокод не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Promo code not found.")
     *         )
     *     )
     * )
     */
    public function usage(PromoCode $promoCode)
    {
        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Promo code not found.'
            ], 404);
        }

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

