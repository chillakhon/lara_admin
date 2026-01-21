<?php

namespace App\Http\Controllers\Api\Admin\OtoBanner;

use App\DTOs\OtoBanner\CreateOtoBannerDTO;
use App\DTOs\OtoBanner\UpdateOtoBannerDTO;
use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\OtoBanner\AttachManagerRequest;
use App\Http\Requests\OtoBanner\StoreOtoBannerRequest;
use App\Http\Requests\OtoBanner\UpdateOtoBannerRequest;

use App\Http\Resources\OtoBanner\OtoBannerListResource;
use App\Http\Resources\OtoBanner\OtoBannerResource;
use App\Http\Resources\OtoBanner\OtoBannerSubmissionResource;
use App\Models\Oto\OtoBanner;
use App\Services\OtoBanner\OtoBannerClientService;
use App\Services\OtoBanner\OtoBannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtoBannerController extends Controller
{
    public function __construct(
        private readonly OtoBannerService $service,
        private readonly OtoBannerClientService $clientService,
    ) {}

    /**
     * Получить список баннеров
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);

        $filters = [
            'status' => $request->input('status'),
            'device_type' => $request->input('device_type'),
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $banners = $this->service->getBanners($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => OtoBannerResource::collection($banners->items()),
            'meta' => PaginationHelper::format($banners),
        ]);
    }

    /**
     * Создать новый баннер
     */
    public function store(StoreOtoBannerRequest $request): JsonResponse
    {
        $dto = CreateOtoBannerDTO::fromRequest($request);
        $banner = $this->service->createBanner($dto);

        return response()->json([
            'success' => true,
            'message' => 'OTO баннер успешно создан',
            'data' => OtoBannerResource::make($banner),
        ], 201);
    }

    /**
     * Получить баннер по ID
     */
    public function show(OtoBanner $otoBanner): JsonResponse
    {
        $banner = $this->service->getBannerById($otoBanner->id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Баннер не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => OtoBannerResource::make($banner),
        ]);
    }

    /**
     * Обновить баннер
     */
    public function update(UpdateOtoBannerRequest $request, OtoBanner $otoBanner): JsonResponse
    {
        $dto = UpdateOtoBannerDTO::fromRequest($request);
        $banner = $this->service->updateBanner($otoBanner, $dto);

        return response()->json([
            'success' => true,
            'message' => 'OTO баннер успешно обновлён',
            'data' => OtoBannerResource::make($banner),
        ]);
    }

    /**
     * Удалить баннер
     */
    public function destroy(OtoBanner $otoBanner): JsonResponse
    {
        $this->service->deleteBanner($otoBanner);

        return response()->json([
            'success' => true,
            'message' => 'OTO баннер успешно удалён',
        ]);
    }

    /**
     * Дублировать баннер
     */
    public function duplicate(OtoBanner $otoBanner): JsonResponse
    {
        $newBanner = $this->service->duplicateBanner($otoBanner);

        $newBanner->load('mainImage');

        return response()->json([
            'success' => true,
            'message' => 'OTO баннер успешно дублирован',
            'data' => OtoBannerResource::make($newBanner),
        ], 201);
    }

    /**
     * Переключить статус баннера
     */
    public function toggleStatus(OtoBanner $otoBanner): JsonResponse
    {
        $this->service->toggleStatus($otoBanner);
        $banner = $otoBanner->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Статус баннера изменён',
            'data' => OtoBannerResource::make($banner),
        ]);
    }

    /**
     * Получить заявки по баннеру
     */
    public function submissions(Request $request, OtoBanner $otoBanner): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $submissions = $this->clientService->getBannerSubmissions($otoBanner, $perPage);

        return response()->json([
            'success' => true,
            'data' => OtoBannerSubmissionResource::collection($submissions->items()),
            'meta' => PaginationHelper::format($submissions),
        ]);
    }

    /**
     * Получить все OTO заявки
     */
    public function allSubmissions(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);

        $filters = [
            'banner_id' => $request->input('banner_id'),
            'status' => $request->input('status'),
            'manager_id' => $request->input('manager_id'),
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $submissions = $this->clientService->getAllSubmissions($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => OtoBannerSubmissionResource::collection($submissions->items()),
            'meta' => PaginationHelper::format($submissions),
        ]);
    }

    /**
     * Прикрепить менеджера к заявке
     */
    public function attachManager(AttachManagerRequest $request, int $submissionId): JsonResponse
    {
        $submission = \App\Models\ContactRequest::findOrFail($submissionId);

        $this->service->attachManager($submission, $request->input('manager_id'));

        return response()->json([
            'success' => true,
            'message' => 'Менеджер успешно прикреплён к заявке',
            'data' => OtoBannerSubmissionResource::make($submission->fresh(['manager.profile'])),
        ]);
    }



}
