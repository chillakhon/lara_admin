<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PromoCode;
use App\Services\PromoCode\PromoCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromoCodeController extends Controller
{

    protected $promoCodeService;

    public function __construct(
        PromoCodeService $promoService
    )
    {
        $this->promoCodeService = $promoService;
    }

    public function index(Request $request)
    {
        $query = PromoCode::query();

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }


        if ($request->filled('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($isActive)) {
                $query->where('is_active', $isActive);
            }
        }

        // Пагинация (по умолчанию 15 на страницу)
        $perPage = $request->get('per_page', 15);
        $promos = $query->paginate($perPage);


        return response()->json([
            'success' => true,
            'data' => $promos->items(),
            'meta' => [
                'current_page' => $promos->currentPage(),
                'last_page' => $promos->lastPage(),
                'per_page' => $promos->perPage(),
                'total' => $promos->total(),
            ]
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:promo_codes,code',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'discount_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_behavior' => 'required|in:replace,stack,skip',
            'starts_at' => 'nullable|date',
//            'expires_at' => 'nullable|date|after_or_equal:starts_at',

            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
                \Illuminate\Validation\Rule::requiredIf(fn() => !$request->boolean('is_unlimited', false)),
            ],
            'is_unlimited' => 'sometimes|boolean',

            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
            'applies_to_all_products' => 'boolean',
            'applies_to_all_clients' => 'boolean',

            'products_with_variants' => 'nullable',


            'template_type' => 'nullable|in:regular,birthday',
            'type' => 'nullable|in:all,specific',
        ]);

        // Обработка загрузки изображения
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('promo_codes', 'public');
            $validated['image'] = $imagePath;
        }

        $promo = PromoCode::create($validated);


        if (!empty($validated['products_with_variants'])) {
            foreach ($validated['products_with_variants'] as $productId => $variantIds) {
                if (empty($variantIds)) {
                    $promo->products()->attach([
                        $productId => ['product_variant_id' => null]
                    ]);
                } else {
                    foreach ($variantIds as $variantId) {
                        $promo->products()->attach([
                            $productId => ['product_variant_id' => $variantId]
                        ]);
                    }
                }
            }
        }


        // Привязка к конкретным клиентам, если указаны
        if (!empty($validated['client_ids'])) {
            $promo->clients()->attach($validated['client_ids']);
        }


        // Загружаем промокод с клиентами для ответа
        $promo->load('clients');

        return response()->json([
            'success' => true,
            'message' => 'Промокод создан',
            'data' => $promo,
        ], 201);
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:promo_codes,code,' . $promoCode->id,
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'discount_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_behavior' => 'required|in:replace,stack,skip',
            'starts_at' => 'nullable|date',
//            'expires_at' => 'nullable|date|after_or_equal:starts_at',

            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
                \Illuminate\Validation\Rule::requiredIf(fn() => !$request->boolean('is_unlimited', false)),
            ],

            'is_unlimited' => 'sometimes|boolean',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
            'applies_to_all_products' => 'boolean',
            'applies_to_all_clients' => 'boolean',

            'products_with_variants' => 'nullable',
            'template_type' => 'nullable|in:regular,birthday',

            'type' => 'nullable|in:all,specific',
        ]);

        // Обработка загрузки изображения
        if ($request->hasFile('image')) {
            // Удаляем старое изображение, если было
            if ($promoCode->image && \Storage::disk('public')->exists($promoCode->image)) {
                \Storage::disk('public')->delete($promoCode->image);
            }

            $imagePath = $request->file('image')->store('promo_codes', 'public');
            $validated['image'] = $imagePath;
        }

        $promoCode->update($validated);

        // Обновляем клиентов
        if (isset($validated['client_ids'])) {
            $promoCode->clients()->sync($validated['client_ids']);
        }

        if (!empty($validated['products_with_variants'])) {

            $promoCode->products()->detach(); // чистим старые связи

            foreach ($validated['products_with_variants'] as $productId => $variantIds) {
                // Если variantIds пустой массив, значит добавляем продукт без конкретного варианта
                if (empty($variantIds)) {
                    $promoCode->products()->attach([
                        $productId => ['product_variant_id' => null]
                    ]);
                } else {
                    foreach ($variantIds as $variantId) {
                        $promoCode->products()->attach([
                            $productId => ['product_variant_id' => $variantId]
                        ]);
                    }
                }
            }
        }


        $promoCode->load('clients');

        return response()->json([
            'success' => true,
            'message' => 'Промокод обновлён',
            'data' => $promoCode,
        ]);
    }

    public function destroy(PromoCode $promoCode)
    {
        // Удаляем изображение, если есть
        if ($promoCode->image && \Storage::disk('public')->exists($promoCode->image)) {
            \Storage::disk('public')->delete($promoCode->image);
        }

        $promoCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Промокод удалён'
        ]);
    }


    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'client_id' => 'nullable|exists:clients,id',
            'product_ids' => 'nullable|array',
        ]);

        $result = $this->promoCodeService->validatePromo($request);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return response()->json($result);
    }


    public function getImage(Request $request)
    {
        $path = $request->get('path');

        if (!$path) {
            return response()->json(['message' => 'Path is required'], 400);
        }

        // Безопасно очистим путь, чтобы избежать directory traversal
        $cleanPath = basename($path);

        $filePath = storage_path("app/public/promo_codes/{$cleanPath}");

        if (!file_exists($filePath)) {
            $filePath = public_path('images/default.png');
        }

        return response()->file($filePath);
    }


}
