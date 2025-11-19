<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PromoCode;
use App\Models\PromoCodeClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PromoCodeClientController extends Controller
{
    /**
     * Получить все связи промокодов с клиентами (с пагинацией и фильтрацией)
     */
    public function index(Request $request): JsonResponse
    {
        $query = PromoCodeClient::with(['promoCode', 'client']);

        // Фильтр по промокоду
        if ($request->filled('promo_code_id')) {
            $query->forPromoCode($request->promo_code_id);
        }

        // Фильтр по клиенту
        if ($request->filled('client_id')) {
            $query->forClient($request->client_id);
        }

        // Фильтр только активных промокодов
        if ($request->filled('active_only') && $request->boolean('active_only')) {
            $query->activePromoCodes();
        }

        // Поиск по коду промокода
        if ($request->filled('promo_code')) {
            $query->whereHas('promoCode', function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->promo_code . '%');
            });
        }

        // Поиск по имени/email клиента
        if ($request->filled('client_search')) {
            $query->whereHas('client', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->client_search . '%')
                    ->orWhere('email', 'like', '%' . $request->client_search . '%');
            });
        }

        // Сортировка
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 15);
        $relations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Список связей промокодов с клиентами получен',
            'data' => $relations->items(),
            'meta' => [
                'current_page' => $relations->currentPage(),
                'last_page' => $relations->lastPage(),
                'per_page' => $relations->perPage(),
                'total' => $relations->total(),
                'from' => $relations->firstItem(),
                'to' => $relations->lastItem(),
            ]
        ]);
    }


    public function getAvailablePromoCodes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        $clientId = $validated['client_id'];

        // Получаем активные промокоды, доступные для клиента
        $query = PromoCode::where('is_active', true)
            ->where(function ($q) {
                // Проверка срока действия
                $q->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->where(function ($q) {
                // Проверка даты начала
                $q->where('starts_at', '<=', now())
                    ->orWhereNull('starts_at');
            })
            ->where(function ($q) use ($clientId) {
                $q->where('applies_to_all_clients', true)
                    ->orWhereHas('clients', function ($subQ) use ($clientId) {
                        $subQ->where('applies_to_all_clients', false)
                            ->where('client_id', $clientId);
                    });
            });



        // Исключаем уже использованные промокоды этим клиентом
        $query->whereDoesntHave('usages', function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        });

        // Проверяем лимит использований через подсчет записей в usages
        $query->where(function ($q) {
            $q->whereNull('max_uses')
                ->orWhere(function ($subQ) {
                    $subQ->whereRaw('(SELECT COUNT(*) FROM promo_code_usages WHERE promo_code_usages.promo_code_id = promo_codes.id AND promo_code_usages.deleted_at IS NULL) < max_uses');
                });
        });

        // Поиск по коду промокода (если нужно)
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        // Фильтр по типу скидки
        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        // Сортировка (по умолчанию по дате создания)
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Разрешенные поля для сортировки
        $allowedSortFields = ['created_at', 'expires_at', 'discount_amount', 'code'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy('promo_codes.' . $sortField, $sortDirection);
        } else {
            $query->orderBy('promo_codes.created_at', 'desc');
        }

        // Получаем промокоды с полями из promo_code_client
        $promoCodes = $query->leftJoin('promo_code_client', function ($join) use ($clientId) {
            $join->on('promo_codes.id', '=', 'promo_code_client.promo_code_id')
                ->where('promo_code_client.client_id', '=', $clientId);
        })
            ->get([
                'promo_codes.id',
                'promo_codes.code',
                'promo_codes.description',
                'promo_codes.image',
                'promo_codes.discount_amount',
                'promo_codes.discount_type',
                'promo_codes.expires_at',
                'promo_codes.max_uses',
                'promo_code_client.notified_at',
                'promo_code_client.birthday_discount',
            ]);

        // Добавляем дополнительную информацию для каждого промокода
        $promoCodes->each(function ($promoCode) {

            // Считаем количество использований и оставшиеся использования
            if ($promoCode->max_uses) {
                $usedCount = $promoCode->usages()->count();
                $promoCode->used_count = $usedCount;
                $promoCode->remaining_uses = max(0, $promoCode->max_uses - $usedCount);
            } else {
                $promoCode->used_count = $promoCode->usages()->count();
                $promoCode->remaining_uses = null; // Безлимитный
            }

            // Добавляем информацию о том, истекает ли скоро промокод
            $promoCode->expires_soon = $promoCode->expires_at
                ? $promoCode->expires_at->diffInDays(now()) <= 7
                : false;

            $promoCode->image_url = $promoCode->image
                ? asset('storage/' . $promoCode->image)
                : null;

        });

        return response()->json([
            'success' => true,
            'message' => 'Доступные промокоды получены',
            'data' => $promoCodes,
            'total' => $promoCodes->count(),
            'client_id' => $clientId,
        ]);
    }

    /**
     * Создать новую связь промокода с клиентом
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'promo_code_id' => 'required|exists:promo_codes,id',
            'client_id' => 'required|exists:clients,id',
        ]);

        // Проверяем, не существует ли уже такая связь
        $existingRelation = PromoCodeClient::forPromoCode($validated['promo_code_id'])
            ->forClient($validated['client_id'])
            ->first();

        if ($existingRelation) {
            return response()->json([
                'success' => false,
                'message' => 'Связь между промокодом и клиентом уже существует',
                'data' => $existingRelation->load(['promoCode', 'client'])
            ], 409);
        }

        $relation = PromoCodeClient::create($validated);
        $relation->load(['promoCode', 'client']);

        return response()->json([
            'success' => true,
            'message' => 'Связь промокода с клиентом создана',
            'data' => $relation,
        ], 201);
    }

    /**
     * Показать конкретную связь промокода с клиентом
     */
    public function show(PromoCodeClient $promoCodeClient): JsonResponse
    {
        $promoCodeClient->load(['promoCode', 'client']);

        return response()->json([
            'success' => true,
            'message' => 'Связь промокода с клиентом найдена',
            'data' => $promoCodeClient,
        ]);
    }

    /**
     * Удалить связь промокода с клиентом
     */
    public function destroy(PromoCodeClient $promoCodeClient): JsonResponse
    {
        $promoCode = $promoCodeClient->promoCode->code;
        $clientName = $promoCodeClient->client->name;

        $promoCodeClient->delete();

        return response()->json([
            'success' => true,
            'message' => "Связь промокода '{$promoCode}' с клиентом '{$clientName}' удалена",
        ]);
    }

    /**
     * Получить всех клиентов, привязанных к промокоду
     */
    public function getPromoCodeClients(PromoCode $promoCode): JsonResponse
    {
        $clients = $promoCode->clients()->withPivot('created_at', 'updated_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Список клиентов промокода получен',
            'data' => $clients,
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'description' => $promoCode->description,
            ],
            'total_clients' => $clients->count(),
        ]);
    }

    /**
     * Получить все промокоды, привязанные к клиенту
     */
    public function getClientPromoCodes(Client $client): JsonResponse
    {
        $promoCodes = $client->promoCodes()
            ->withPivot('created_at', 'updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Список промокодов клиента получен',
            'data' => $promoCodes,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ],
            'total_promo_codes' => $promoCodes->count(),
        ]);
    }

    /**
     * Привязать промокод к нескольким клиентам
     */
    public function attachClients(Request $request, PromoCode $promoCode): JsonResponse
    {
        $validated = $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
        ]);

        // Проверяем, какие клиенты уже привязаны
        $existingClientIds = $promoCode->clients()->pluck('client_id')->toArray();
        $newClientIds = array_diff($validated['client_ids'], $existingClientIds);

        if (empty($newClientIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Все указанные клиенты уже привязаны к этому промокоду',
                'existing_clients' => Client::whereIn('id', $validated['client_ids'])->get(['id', 'name', 'email']),
            ], 400);
        }

        // Привязываем только новых клиентов
        $promoCode->clients()->attach($newClientIds);

        $attachedClients = Client::whereIn('id', $newClientIds)->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'message' => 'Клиенты успешно привязаны к промокоду',
            'attached_clients' => $attachedClients,
            'total_attached' => count($newClientIds),
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
            ],
        ]);
    }

    /**
     * Отвязать промокод от нескольких клиентов
     */
    public function detachClients(Request $request, PromoCode $promoCode): JsonResponse
    {
        $validated = $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
        ]);

        // Проверяем, какие клиенты действительно привязаны
        $existingClientIds = $promoCode->clients()->pluck('client_id')->toArray();
        $clientsToDetach = array_intersect($validated['client_ids'], $existingClientIds);

        if (empty($clientsToDetach)) {
            return response()->json([
                'success' => false,
                'message' => 'Ни один из указанных клиентов не привязан к этому промокоду',
            ], 400);
        }

        $promoCode->clients()->detach($clientsToDetach);

        $detachedClients = Client::whereIn('id', $clientsToDetach)->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'message' => 'Клиенты успешно отвязаны от промокода',
            'detached_clients' => $detachedClients,
            'total_detached' => count($clientsToDetach),
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
            ],
        ]);
    }

    /**
     * Привязать клиента к нескольким промокодам
     */
    public function attachPromoCodes(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'promo_code_ids' => 'required|array|min:1',
            'promo_code_ids.*' => 'exists:promo_codes,id',
        ]);

        // Проверяем, какие промокоды уже привязаны
        $existingPromoCodeIds = $client->promoCodes()->pluck('promo_code_id')->toArray();
        $newPromoCodeIds = array_diff($validated['promo_code_ids'], $existingPromoCodeIds);

        if (empty($newPromoCodeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Все указанные промокоды уже привязаны к этому клиенту',
                'existing_promo_codes' => PromoCode::whereIn('id', $validated['promo_code_ids'])->get(['id', 'code', 'description']),
            ], 400);
        }

        // Привязываем только новые промокоды
        $client->promoCodes()->attach($newPromoCodeIds);

        $attachedPromoCodes = PromoCode::whereIn('id', $newPromoCodeIds)->get(['id', 'code', 'description']);

        return response()->json([
            'success' => true,
            'message' => 'Промокоды успешно привязаны к клиенту',
            'attached_promo_codes' => $attachedPromoCodes,
            'total_attached' => count($newPromoCodeIds),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ],
        ]);
    }

    /**
     * Отвязать клиента от нескольких промокодов
     */
    public function detachPromoCodes(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'promo_code_ids' => 'required|array|min:1',
            'promo_code_ids.*' => 'exists:promo_codes,id',
        ]);

        // Проверяем, какие промокоды действительно привязаны
        $existingPromoCodeIds = $client->promoCodes()->pluck('promo_code_id')->toArray();
        $promoCodesToDetach = array_intersect($validated['promo_code_ids'], $existingPromoCodeIds);

        if (empty($promoCodesToDetach)) {
            return response()->json([
                'success' => false,
                'message' => 'Ни один из указанных промокодов не привязан к этому клиенту',
            ], 400);
        }

        $client->promoCodes()->detach($promoCodesToDetach);

        $detachedPromoCodes = PromoCode::whereIn('id', $promoCodesToDetach)->get(['id', 'code', 'description']);

        return response()->json([
            'success' => true,
            'message' => 'Промокоды успешно отвязаны от клиента',
            'detached_promo_codes' => $detachedPromoCodes,
            'total_detached' => count($promoCodesToDetach),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ],
        ]);
    }

    /**
     * Массовое создание связей (привязка одного промокода к множеству клиентов)
     */
    public function bulkAttach(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'promo_code_id' => 'required|exists:promo_codes,id',
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $promoCode = PromoCode::find($validated['promo_code_id']);

        // Получаем уже существующие связи
        $existingClientIds = $promoCode->clients()->pluck('client_id')->toArray();
        $newClientIds = array_diff($validated['client_ids'], $existingClientIds);

        $results = [
            'attached' => 0,
            'skipped' => 0,
            'attached_clients' => [],
            'skipped_clients' => [],
        ];

        // Добавляем новые связи
        if (!empty($newClientIds)) {
            $promoCode->clients()->attach($newClientIds);
            $results['attached'] = count($newClientIds);
            $results['attached_clients'] = Client::whereIn('id', $newClientIds)->get(['id', 'name', 'email']);
        }

        // Учитываем пропущенные (уже существующие)
        $skippedClientIds = array_intersect($validated['client_ids'], $existingClientIds);
        if (!empty($skippedClientIds)) {
            $results['skipped'] = count($skippedClientIds);
            $results['skipped_clients'] = Client::whereIn('id', $skippedClientIds)->get(['id', 'name', 'email']);
        }

        return response()->json([
            'success' => true,
            'message' => "Массовое привязывание завершено. Добавлено: {$results['attached']}, Пропущено: {$results['skipped']}",
            'data' => $results,
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'description' => $promoCode->description,
            ],
        ]);
    }

    /**
     * Синхронизация связей (заменить всех клиентов промокода)
     */
    public function syncClients(Request $request, PromoCode $promoCode): JsonResponse
    {
        $validated = $request->validate([
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        // Синхронизируем связи (Laravel автоматически добавит новые и удалит старые)
        $syncResult = $promoCode->clients()->sync($validated['client_ids']);

        $attached = Client::whereIn('id', $syncResult['attached'])->get(['id', 'name', 'email']);
        $detached = Client::whereIn('id', $syncResult['detached'])->get(['id', 'name', 'email']);
        $updated = Client::whereIn('id', $syncResult['updated'])->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'message' => 'Синхронизация клиентов промокода завершена',
            'data' => [
                'attached' => $attached,
                'detached' => $detached,
                'updated' => $updated,
                'counts' => [
                    'attached' => count($syncResult['attached']),
                    'detached' => count($syncResult['detached']),
                    'updated' => count($syncResult['updated']),
                ],
            ],
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'description' => $promoCode->description,
            ],
        ]);
    }

    /**
     * Получить статистику связей
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_relations' => PromoCodeClient::count(),
            'total_promo_codes_with_clients' => PromoCode::whereHas('clients')->count(),
            'total_clients_with_promo_codes' => Client::whereHas('promoCodes')->count(),
            'active_relations' => PromoCodeClient::activePromoCodes()->count(),
            'top_promo_codes' => PromoCode::withCount('clients')
                ->orderBy('clients_count', 'desc')
                ->limit(5)
                ->get(['id', 'code', 'description', 'clients_count']),
            'top_clients' => Client::withCount('promoCodes')
                ->orderBy('promo_codes_count', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'email', 'promo_codes_count']),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Статистика связей промокодов и клиентов',
            'data' => $stats,
        ]);
    }
}
