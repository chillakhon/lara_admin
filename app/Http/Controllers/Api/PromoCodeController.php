<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = PromoCode::query();

        // Поиск по коду промокода
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
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        // Обработка загрузки изображения
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('promo_codes', 'public');
            $validated['image'] = $imagePath;
        }

        $promo = PromoCode::create($validated);

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
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
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

        // Загружаем промокод с клиентами для ответа
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
            // 'amount' => 'required|numeric|min:0'
        ]);

        $client = null;

        if ($request->get('client_id')) {
            $client = Client::find($request->get('client_id'));
        }

        if (!$client) {
            $authenticated = $request->user();
            if ($authenticated instanceof \App\Models\Client) {
                $client = $authenticated;
            } elseif ($authenticated instanceof \App\Models\User) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пожалуйста, укажите client_id — вы авторизованы как администратор, не как клиент.',
                ], 422);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не авторизован.',
                ], 401);
            }
        }

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

        return response()->json([
            'message' => "Купон доступен для использования.",
            'promo_code' => $promoCode,
        ]);
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
