<?php

namespace App\Http\Controllers\Api\Admin\Statuses;

use App\Http\Controllers\Controller;
use App\Services\Status\StatusService;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    protected StatusService $statusService;

    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Получить все статусы для фронта
     */
    public function index(): JsonResponse
    {
        $statuses = $this->statusService->getAllStatuses();

        return $this->successResponse('Статусы успешно получены', $statuses);
    }

    /**
     * Получить только статусы заказов
     */
    public function orderStatuses(): JsonResponse
    {
        $statuses = $this->statusService->getOrderStatuses();

        return $this->successResponse('Статусы заказов получены', $statuses);
    }

    /**
     * Получить только статусы платежей
     */
    public function paymentStatuses(): JsonResponse
    {
        $statuses = $this->statusService->getPaymentStatuses();

        return $this->successResponse('Статусы платежей получены', $statuses);
    }

    /**
     * Получить только статусы обращений
     */
    public function contactRequestStatuses(): JsonResponse
    {
        $statuses = $this->statusService->getContactRequestStatuses();

        return $this->successResponse('Статусы обращений получены', $statuses);
    }

    private function successResponse(string $message, array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
}
