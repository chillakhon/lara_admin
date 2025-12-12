<?php

namespace App\Http\Controllers\Api\Admin\Segment;

use App\Actions\Segment\AttachClientsToSegmentAction;
use App\Actions\Segment\AttachPromoCodeToSegmentAction;
use App\Actions\Segment\CreateSegmentAction;
use App\Actions\Segment\DeleteSegmentAction;
use App\Actions\Segment\DetachClientsFromSegmentAction;
use App\Actions\Segment\DetachPromoCodeFromSegmentAction;
use App\Actions\Segment\RecalculateSegmentClientsAction;
use App\Actions\Segment\UpdateSegmentAction;
use App\DTOs\Segment\CreateSegmentDTO;
use App\DTOs\Segment\SegmentClientFilterDTO;
use App\DTOs\Segment\SegmentExportDTO;
use App\DTOs\Segment\UpdateSegmentDTO;
use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Segment\AttachClientsRequest;
use App\Http\Requests\Segment\AttachPromoCodesRequest;
use App\Http\Requests\Segment\ExportSegmentRequest;
use App\Http\Requests\Segment\FilterSegmentClientsRequest;
use App\Http\Requests\Segment\StoreSegmentRequest;
use App\Http\Requests\Segment\UpdateSegmentRequest;
use App\Http\Resources\Segment\SegmentClientResource;
use App\Http\Resources\Segment\SegmentDetailResource;
use App\Http\Resources\Segment\SegmentListResource;
use App\Http\Resources\Segment\SegmentResource;
use App\Http\Resources\Segment\SegmentStatisticsResource;
use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use App\Services\Segment\SegmentExportService;
use App\Services\Segment\SegmentService;
use App\Services\Segment\SegmentStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Sleep;

class SegmentController extends Controller
{
    public function __construct(
        protected SegmentService                   $segmentService,
        protected SegmentRepository                $segmentRepository,
        protected SegmentStatisticsService         $statisticsService,
        protected SegmentExportService             $exportService,
        protected CreateSegmentAction              $createAction,
        protected UpdateSegmentAction              $updateAction,
        protected DeleteSegmentAction              $deleteAction,
        protected RecalculateSegmentClientsAction  $recalculateAction,
        protected AttachClientsToSegmentAction     $attachClientsAction,
        protected DetachClientsFromSegmentAction   $detachClientsAction,
        protected AttachPromoCodeToSegmentAction   $attachPromoCodeAction,
        protected DetachPromoCodeFromSegmentAction $detachPromoCodeAction
    )
    {
    }

    /**
     * Получить список всех сегментов
     *
     * GET /api/segments
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->get('search'),
            'is_active' => $request->get('is_active'),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_direction' => $request->get('sort_direction', 'desc'),
        ];

        $perPage = (int)$request->get('per_page', 15);
        $segments = $this->segmentRepository->paginate($filters, $perPage);

        // Добавляем краткую статистику к каждому сегменту
        $segments->getCollection()->transform(function ($segment) {
            $segment->statistics = $this->statisticsService->getBriefStatistics($segment);
            return $segment;
        });


        return response()->json([
            'data' => SegmentListResource::collection($segments->items()),
            'meta' => PaginationHelper::format($segments),
        ]);
    }

    /**
     * Создать новый сегмент
     *
     * POST /api/segments
     */
    public function store(StoreSegmentRequest $request): JsonResponse
    {
        try {
            $dto = CreateSegmentDTO::fromArray($request->validated());
            $segment = $this->createAction->execute($dto);

            // Если есть условия, пересчитываем клиентов
            if (!empty($dto->conditions)) {
                $this->recalculateAction->execute($segment);
            }

            return response()->json([
                'success' => true,
                'message' => 'Сегмент успешно создан',
                'data' => new SegmentResource($segment->fresh(['clients', 'promoCodes'])),
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании сегмента',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить детальную информацию о сегменте
     *
     * GET /api/segments/{id}
     */
    public function show(Segment $segment): JsonResponse
    {
        try {
            // Пересчитываем клиентов, если нужно
            if ($segment->needsRecalculation()) {
                $this->recalculateAction->execute($segment);
                $segment = $segment->fresh(['clients', 'promoCodes']);
            }


            //  Загружаем промокоды, если ещё не загружены
            if (!$segment->relationLoaded('promoCodes')) {
                $segment->load('promoCodes');
            }

            //  Загружаем count
            $segment->loadCount('promoCodes');

            // Получаем статистику
            $statistics = $this->statisticsService->getBriefStatistics($segment);
            $segment->statistics = $statistics;


            return response()->json([
                'success' => true,
                'data' => new SegmentDetailResource($segment),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении сегмента',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить сегмент
     *
     * PUT /api/segments/{id}
     */
    public function update(UpdateSegmentRequest $request, Segment $segment): JsonResponse
    {
        try {
            $dto = UpdateSegmentDTO::fromArray($request->validated());
            $updatedSegment = $this->updateAction->execute($segment, $dto);

            // Если изменились условия, пересчитываем клиентов
            if (isset($dto->conditions)) {
                $this->recalculateAction->execute($updatedSegment);
            }

            return response()->json([
                'success' => true,
                'message' => 'Сегмент успешно обновлён',
                'data' => new SegmentResource($updatedSegment->fresh(['clients', 'promoCodes'])),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении сегмента',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить сегмент
     *
     * DELETE /api/segments/{id}
     */
    public function destroy(Segment $segment): JsonResponse
    {

        try {

            $this->deleteAction->execute($segment);

            return response()->json([
                'success' => true,
                'message' => 'Сегмент успешно удалён',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении сегмента',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить клиентов сегмента с фильтрацией
     */
    public function getClients(FilterSegmentClientsRequest $request, Segment $segment): JsonResponse
    {
        $filters = SegmentClientFilterDTO::fromRequest($request->validated());
        $clients = $this->segmentRepository->getSegmentClients($segment, $filters);

        return response()->json([
            'data' => SegmentClientResource::collection($clients->items()),
            'meta' => PaginationHelper::format($clients),
        ]);
    }


    public function getAvailableClients(FilterSegmentClientsRequest $request, Segment $segment): JsonResponse
    {
        $filters = SegmentClientFilterDTO::fromRequest($request->validated());
        $clients = $this->segmentRepository->getAvailableClients($segment, $filters);

        return response()->json([
            'data' => SegmentClientResource::collection($clients->items()),
            'meta' => PaginationHelper::format($clients),
        ]);
    }

    /**
     * Добавить клиентов в сегмент
     */
    public function attachClients(AttachClientsRequest $request, Segment $segment): JsonResponse
    {
        try {
            $clientIds = $request->validated()['client_ids'];
            $this->attachClientsAction->execute($segment, $clientIds);

            return response()->json([
                'success' => true,
                'message' => 'Клиенты успешно добавлены в сегмент',
                'data' => [
                    'added_count' => count($clientIds),
                    'total_clients' => $segment->clients()->count(),
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении клиентов',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить клиентов из сегмента
     *
     * DELETE /api/segments/{id}/clients
     */
    public function detachClients(AttachClientsRequest $request, Segment $segment): JsonResponse
    {
        try {
            $clientIds = $request->validated()['client_ids'];
            $this->detachClientsAction->execute($segment, $clientIds);

            return response()->json([
                'success' => true,
                'message' => 'Клиенты успешно удалены из сегмента',
                'data' => [
                    'removed_count' => count($clientIds),
                    'total_clients' => $segment->clients()->count(),
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении клиентов',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Прикрепить промокоды к сегменту
     *
     * POST /api/segments/{id}/promo-codes
     */
    public function attachPromoCodes(AttachPromoCodesRequest $request, Segment $segment): JsonResponse
    {
        try {
            $promoCodeIds = $request->validated()['promo_code_ids'];
            $autoApply = $request->validated()['auto_apply'] ?? true;

            $this->attachPromoCodeAction->execute($segment, $promoCodeIds, $autoApply);


            return response()->json([
                'success' => true,
                'message' => 'Промокоды успешно прикреплены к сегменту',
                'data' => [
                    'attached_count' => count($promoCodeIds),
                    'total_promo_codes' => $segment->promoCodes()->count(),
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при прикреплении промокодов',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Открепить промокоды от сегмента
     *
     * DELETE /api/segments/{id}/promo-codes
     */
    public function detachPromoCodes(AttachPromoCodesRequest $request, Segment $segment): JsonResponse
    {
        try {
            $promoCodeIds = $request->validated()['promo_code_ids'];
            $this->detachPromoCodeAction->execute($segment, $promoCodeIds);

            return response()->json([
                'success' => true,
                'message' => 'Промокоды успешно откреплены от сегмента',
                'data' => [
                    'detached_count' => count($promoCodeIds),
                    'total_promo_codes' => $segment->promoCodes()->count(),
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при откреплении промокодов',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статистику сегмента
     *
     * GET /api/segments/{id}/statistics
     */
    public function statistics(Segment $segment): JsonResponse
    {
        try {
            $statistics = $this->statisticsService->getStatistics($segment);

            return response()->json([
                'success' => true,
                'data' => new SegmentStatisticsResource($statistics),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении статистики',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Экспортировать клиентов сегмента в CSV
     *
     * GET /api/segments/{id}/export
     */
    public function export(ExportSegmentRequest $request, Segment $segment)
    {
        try {
            if (!$this->exportService->canExport($segment)) {
                return response()->json([
                    'success' => false,
                    'message' => 'В сегменте нет клиентов для экспорта',
                ], 400);
            }

            $dto = SegmentExportDTO::fromRequest($segment->id, $request->validated());
            return $this->exportService->exportToCSV($segment, $dto);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при экспорте сегмента',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить предпросмотр экспорта
     *
     * GET /api/segments/{id}/export-preview
     */
    public function exportPreview(ExportSegmentRequest $request, Segment $segment): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? [];
            $preview = $this->exportService->getExportPreview($segment, $columns);
            $count = $this->exportService->getExportCount($segment);

            return response()->json([
                'success' => true,
                'data' => [
                    'preview' => $preview,
                    'total_count' => $count,
                    'headers' => SegmentExportDTO::fromRequest($segment->id, $request->validated())->getSelectedHeaders(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении предпросмотра',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Пересчитать клиентов сегмента вручную
     *
     * POST /api/segments/{id}/recalculate
     */
    public function recalculate(Segment $segment): JsonResponse
    {
        try {
            $this->recalculateAction->execute($segment);

            return response()->json([
                'success' => true,
                'message' => 'Клиенты сегмента успешно пересчитаны',
                'data' => [
                    'clients_count' => $segment->clients()->count(),
                    'last_recalculated_at' => $segment->fresh()->last_recalculated_at->format('d.m.Y H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при пересчёте клиентов',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Переключить активность сегмента
     *
     * POST /api/segments/{id}/toggle-active
     */
    public function toggleActive(Segment $segment): JsonResponse
    {
        try {
            $updatedSegment = $this->segmentService->toggleActive($segment);

            return response()->json([
                'success' => true,
                'message' => $updatedSegment->is_active
                    ? 'Сегмент активирован'
                    : 'Сегмент деактивирован',
                'data' => [
                    'is_active' => $updatedSegment->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при изменении активности',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
