<?php

namespace App\Http\Controllers\Api\Public\Promotion;

use App\Http\Controllers\Controller;
use App\Services\Promotion\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromotionPublicController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    /**
     * Проверить применимые акции для корзины
     */
    public function checkApplicable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $promotions = $this->promotionService->findApplicablePromotions(
            $validated['items'],
            $validated['total']
        );

        return response()->json([
            'success' => true,
            'data' => $promotions->map(function ($promotion) {
                return [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'description' => $promotion->description,
                    'allow_promo_codes' => $promotion->allow_promo_codes,
                    'min_purchase_amount' => $promotion->min_purchase_amount,
                    'priority' => $promotion->priority,
                    'gift_products' => $promotion->giftProducts->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->price,
                            'quantity' => $product->pivot->quantity,
                            'image' => $product->images->first()?->url ?? null,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Получить список активных акций
     */
    public function index(Request $request): JsonResponse
    {
        $promotions = \App\Models\Promotion::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('max_uses')
                    ->orWhereRaw('times_used < max_uses');
            })
            ->with(['triggerProducts', 'giftProducts.images'])
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $promotions->map(function ($promotion) {
                return [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'description' => $promotion->description,
                    'min_purchase_amount' => $promotion->min_purchase_amount,
                    'allow_promo_codes' => $promotion->allow_promo_codes,
                    'starts_at' => $promotion->starts_at,
                    'ends_at' => $promotion->ends_at,
                    'trigger_products' => $promotion->triggerProducts->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                        ];
                    }),
                    'gift_products' => $promotion->giftProducts->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->price,
                            'quantity' => $product->pivot->quantity,
                            'image' => $product->images->first()?->url ?? null,
                        ];
                    }),
                ];
            }),
        ]);
    }
}
