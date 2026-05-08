<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderAuthorizationService;
use App\Services\Order\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderViewController extends Controller
{
    public function __construct(
        protected OrderAuthorizationService $orderAuthorizationService,
        protected OrderCreationService $orderCreationService,
    ) {}

    /**
     * Агрегированные данные заказа для страницы просмотра.
     * Возвращает order + все связанные сущности и виджеты (history, payments, neighbors, ...).
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (! $this->orderAuthorizationService->canView($user, $order)) {
            return $this->errorResponse('Доступ запрещён', 403);
        }

        $order->load([
            'items.product.images',
            'items.variant.images',
            'items.color',
            'client.profile',
            'promoCode',
            'promotion',
            'giftCard',
            'address',
            'deliveryMethod',
            'deliveryTarget',
            'deliveryZone',
            'deliveryDate',
            'lead',
            'history.user.roles',
            'payments',
            'tasks.status',
            'tasks.priority',
            'tasks.assignee.profile',
        ]);

        $summary = $this->orderCreationService->getOrderSummary($order);

        $client = $order->client;
        $clientStats = null;
        if ($client) {
            $clientStats = [
                'orders_count' => $client->orders()->count(),
                'orders_total' => (float) $client->orders()->sum('total_amount'),
            ];
        }

        // prev/next по id (можно поменять на created_at, если нужно)
        $prevId = Order::where('id', '<', $order->id)
            ->orderByDesc('id')
            ->value('id');
        $nextId = Order::where('id', '>', $order->id)
            ->orderBy('id')
            ->value('id');

        return $this->successResponse('Просмотр заказа', [
            'order' => $order,
            'summary' => $summary,
            'client_stats' => $clientStats,
            'history' => $this->formatHistory($order),
            'payments' => $order->payments,
            'tasks' => $this->formatTasks($order),
            'custom_fields' => [],    // TODO: модель CustomFieldValue не привязана к orders
            'viewed_products' => [],  // TODO: трекинг просмотров клиента
            'source' => [
                'utm_source' => $order->utm_source,
                'utm_medium' => $order->utm_medium,
                'utm_campaign' => $order->utm_campaign,
                'utm_content' => $order->utm_content,
                'utm_term' => $order->utm_term,
                'ip_address' => $order->ip_address,
                'user_agent' => $order->user_agent,
            ],
            'neighbors' => [
                'prev_id' => $prevId,
                'next_id' => $nextId,
            ],
        ]);
    }

    /**
     * Возвращает историю заказа в формате для фронтенда:
     *   id, action, description, created_at, user: { id, name, role }
     * Роль — name первой роли пользователя (или null, если ролей нет).
     */
    private function formatHistory(Order $order): array
    {
        return $order->history()
            ->with('user.roles')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($entry) {
                $user = $entry->user;
                $role = $user?->roles->first();

                return [
                    'id' => $entry->id,
                    'action' => $entry->action,
                    'description' => $entry->description ?? $entry->comment,
                    'created_at' => $entry->created_at,
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->get_full_name() ?: ($user->email ?? null),
                        'role' => $role?->name,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Формирует компактный массив задач заказа для правого блока на странице просмотра.
     */
    private function formatTasks(Order $order): array
    {
        return $order->tasks
            ->sortByDesc('id')
            ->map(function ($task) {
                $assignee = $task->assignee;
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'due_date' => $task->due_date,
                    'completed_at' => $task->completed_at,
                    'is_overdue' => $task->isOverdue(),
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assignee' => $assignee ? [
                        'id' => $assignee->id,
                        'name' => data_get($assignee, 'profile.full_name') ?: $assignee->email,
                        'email' => $assignee->email,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    private function successResponse(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            ...$data,
        ], $status);
    }

    private function errorResponse(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            ...$extra,
        ], $status);
    }
}
