<?php

namespace App\Http\Controllers\Api\Admin\GiftCard;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\GiftCard\CancelGiftCardRequest;
use App\Http\Requests\GiftCard\ResendGiftCardRequest;
use App\Http\Resources\GiftCard\GiftCardResource;
use App\Http\Resources\GiftCard\GiftCardSummaryResource;
use App\Models\GiftCard\GiftCard;
use App\Services\GiftCard\GiftCardService;
use App\Services\GiftCard\GiftCardDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GiftCardController extends Controller
{
    public function __construct(
        protected GiftCardService $giftCardService,
        protected GiftCardDeliveryService $deliveryService
    ) {}

    /**
     * Список всех подарочных карт с фильтрацией
     */
    public function index(Request $request): JsonResponse
    {
        $query = GiftCard::with(['purchaseOrder']);

        // Фильтрация по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Фильтрация по типу
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Поиск по коду
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('recipient_email', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%");
            });
        }

        // Фильтрация по дате создания
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Сортировка
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->input('per_page', 15);
        $giftCards = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => GiftCardSummaryResource::collection($giftCards->items()),
            'meta' => PaginationHelper::format($giftCards),
        ]);
    }

    /**
     * Получить детали подарочной карты
     */
    public function show(GiftCard $giftCard): JsonResponse
    {
        $giftCard->load([
            'purchaseOrder.client.profile',
            'transactions.order',
            'usedInOrders.client',
        ]);

        return response()->json([
            'success' => true,
            'data' => new GiftCardResource($giftCard),
        ]);
    }

    /**
     * Аннулировать подарочную карту
     */
    public function cancel(CancelGiftCardRequest $request, GiftCard $giftCard): JsonResponse
    {
        try {
            if ($giftCard->status === GiftCard::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Карта уже аннулирована',
                ], 422);
            }

            $this->giftCardService->cancel($giftCard, $request->input('reason'));

            return response()->json([
                'success' => true,
                'message' => 'Подарочная карта успешно аннулирована',
                'data' => GiftCardSummaryResource::make($giftCard->fresh()),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel gift card', [
                'gift_card_id' => $giftCard->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось аннулировать карту',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Переотправить подарочную карту
     */
    public function resend(ResendGiftCardRequest $request, GiftCard $giftCard): JsonResponse
    {
        try {


            // Обновляем данные получателя если нужно
            $giftCard->update([
                'delivery_channel' => $request->input('delivery_channel'),
                'recipient_email' => $request->input('recipient_email', $giftCard->recipient_email),
//                'recipient_phone' => $request->input('recipient_phone', $giftCard->recipient_phone),
            ]);

            // Отправляем карту
            $this->deliveryService->send($giftCard);

            return response()->json([
                'success' => true,
                'message' => 'Подарочная карта успешно переотправлена',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resend gift card', [
                'gift_card_id' => $giftCard->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось переотправить карту',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Статистика по подарочным картам
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = [
            'total_cards' => GiftCard::count(),
            'active_cards' => GiftCard::where('status', GiftCard::STATUS_ACTIVE)->count(),
            'used_cards' => GiftCard::where('status', GiftCard::STATUS_USED)->count(),
            'cancelled_cards' => GiftCard::where('status', GiftCard::STATUS_CANCELLED)->count(),

            'total_nominal' => (float) GiftCard::sum('nominal'),
            'total_balance' => (float) GiftCard::where('status', GiftCard::STATUS_ACTIVE)->sum('balance'),
            'total_used_amount' => (float) (GiftCard::sum('nominal') - GiftCard::sum('balance')),

            'by_type' => [
                'electronic' => GiftCard::where('type', GiftCard::TYPE_ELECTRONIC)->count(),
                'plastic' => GiftCard::where('type', GiftCard::TYPE_PLASTIC)->count(),
            ],

            'by_nominal' => GiftCard::selectRaw('nominal, count(*) as count')
                ->groupBy('nominal')
                ->get()
                ->pluck('count', 'nominal'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Экспорт в CSV
     */
    public function export(Request $request)
    {
        $query = GiftCard::with(['purchaseOrder', 'transactions']);

        // Применяем те же фильтры что и в index
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $giftCards = $query->get();

        $csvData = [];
        $csvData[] = [
            'ID',
            'Код',
            'Номинал',
            'Баланс',
            'Тип',
            'Статус',
            'Отправитель',
            'Получатель',
            'Email получателя',
            'Дата создания',
            'Дата отправки',
            'Канал доставки',
        ];

        foreach ($giftCards as $card) {
            $csvData[] = [
                $card->id,
                $card->code,
                $card->nominal,
                $card->balance,
                $card->type,
                $card->status,
                $card->sender_name,
                $card->recipient_name,
                $card->recipient_email,
                $card->created_at->format('d.m.Y H:i'),
                $card->sent_at?->format('d.m.Y H:i') ?? 'Не отправлено',
                $card->delivery_channel,
            ];
        }

        $filename = 'gift_cards_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}
