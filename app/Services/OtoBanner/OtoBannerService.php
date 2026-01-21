<?php

namespace App\Services\OtoBanner;

use App\Actions\OtoBanner\CreateOtoBannerAction;
use App\Actions\OtoBanner\DeleteOtoBannerAction;
use App\Actions\OtoBanner\DuplicateOtoBannerAction;
use App\Actions\OtoBanner\ProcessOtoBannerSubmissionAction;
use App\Actions\OtoBanner\ToggleOtoBannerStatusAction;
use App\Actions\OtoBanner\TrackOtoBannerViewAction;
use App\Actions\OtoBanner\UpdateOtoBannerAction;
use App\DTOs\OtoBanner\CreateOtoBannerDTO;
use App\DTOs\OtoBanner\OtoBannerSubmissionDTO;
use App\DTOs\OtoBanner\UpdateOtoBannerDTO;
use App\Enums\Oto\OtoBannerDeviceType;
use App\Models\ContactRequest;
use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OtoBannerService
{
    public function __construct(
        private readonly OtoBannerRepository $repository,
        private readonly CreateOtoBannerAction $createAction,
        private readonly UpdateOtoBannerAction $updateAction,
        private readonly DeleteOtoBannerAction $deleteAction,
        private readonly DuplicateOtoBannerAction $duplicateAction,
        private readonly ToggleOtoBannerStatusAction $toggleStatusAction,
        private readonly TrackOtoBannerViewAction $trackViewAction,
        private readonly ProcessOtoBannerSubmissionAction $processSubmissionAction,
    ) {}

    /**
     * Получить список баннеров с пагинацией
     */
    public function getBanners(int $perPage, array $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters);
    }

    /**
     * Получить баннер по ID
     */
    public function getBannerById(int $id): ?OtoBanner
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Создать новый баннер
     */
    public function createBanner(CreateOtoBannerDTO $dto): OtoBanner
    {
        return $this->createAction->execute($dto);
    }

    /**
     * Обновить баннер
     */
    public function updateBanner(OtoBanner $banner, UpdateOtoBannerDTO $dto): OtoBanner
    {
        return $this->updateAction->execute($banner, $dto);
    }

    /**
     * Удалить баннер
     */
    public function deleteBanner(OtoBanner $banner): bool
    {
        return $this->deleteAction->execute($banner);
    }

    /**
     * Дублировать баннер
     */
    public function duplicateBanner(OtoBanner $banner): OtoBanner
    {
        return $this->duplicateAction->execute($banner);
    }

    /**
     * Переключить статус баннера
     */
    public function toggleStatus(OtoBanner $banner): bool
    {
        return $this->toggleStatusAction->execute($banner);
    }

    /**
     * Получить активный баннер для устройства
     */
    public function getActiveBannerForDevice(OtoBannerDeviceType $deviceType): ?OtoBanner
    {
        return $this->repository->getActiveBannerForDevice($deviceType);
    }

    /**
     * Трекинг просмотра баннера
     */
    public function trackView(
        OtoBanner $banner,
        ?int $clientId,
        string $ipAddress,
        string $userAgent,
        string $sessionId
    ): bool {
        return $this->trackViewAction->execute(
            $banner,
            $clientId,
            $ipAddress,
            $userAgent,
            $sessionId
        );
    }

    /**
     * Обработать отправку формы баннера
     */
    public function processSubmission(OtoBanner $banner, OtoBannerSubmissionDTO $dto): ContactRequest
    {
        return $this->processSubmissionAction->execute($banner, $dto);
    }

    /**
     * Прикрепить менеджера к заявке
     */
    public function attachManager(ContactRequest $submission, int $managerId): bool
    {
        return $submission->update(['manager_id' => $managerId]);
    }
}
