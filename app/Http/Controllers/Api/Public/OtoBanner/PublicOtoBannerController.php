<?php

namespace App\Http\Controllers\Api\Public\OtoBanner;

use App\DTOs\OtoBanner\OtoBannerSubmissionDTO;
use App\Enums\Oto\OtoBannerDeviceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\OtoBanner\SubmitOtoBannerRequest;
use App\Http\Requests\OtoBanner\TrackOtoBannerViewRequest;
use App\Http\Resources\OtoBanner\OtoBannerResource;
use App\Http\Resources\OtoBanner\OtoBannerSubmissionResource;
use App\Models\Oto\OtoBanner;
use App\Services\OtoBanner\OtoBannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicOtoBannerController extends Controller
{
    public function __construct(
        private readonly OtoBannerService $service,
    ) {}

    /**
     * Получить активный баннер для устройства
     */
    public function getActive(Request $request): JsonResponse
    {
        $deviceType = $request->input('device_type', 'desktop');


        Log::debug([
            'deviceType' => $deviceType,
            'test' => 5444
        ]);

        try {
            $deviceTypeEnum = OtoBannerDeviceType::from($deviceType);
        } catch (\ValueError $e) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный тип устройства',
            ], 400);
        }

        $banner = $this->service->getActiveBannerForDevice($deviceTypeEnum);

        if (!$banner) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Активный баннер не найден',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => OtoBannerResource::make($banner),
        ]);
    }

    /**
     * Трекинг просмотра баннера
     */
    public function trackView(TrackOtoBannerViewRequest $request, OtoBanner $otoBanner): JsonResponse
    {
        $clientId = auth('sanctum')->id();

        $tracked = $this->service->trackView(
            banner: $otoBanner,
            clientId: $clientId,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            sessionId: $request->input('session_id'),
        );

        return response()->json([
            'success' => true,
            'tracked' => $tracked,
            'message' => $tracked ? 'Просмотр зафиксирован' : 'Просмотр уже был зафиксирован',
        ]);
    }

    /**
     * Отправить форму баннера
     */
    public function submit(SubmitOtoBannerRequest $request, OtoBanner $otoBanner): JsonResponse
    {

        // Проверяем, активен ли баннер
        if (!$otoBanner->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Баннер неактивен',
            ], 403);
        }

        $dto = OtoBannerSubmissionDTO::fromRequest($request, $otoBanner->id);

        $submission = $this->service->processSubmission($otoBanner, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Заявка успешно отправлена',
            'data' => OtoBannerSubmissionResource::make($submission),
        ], 201);
    }
}
