<?php

namespace App\Services\OtoBanner;

use App\Models\Client;
use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerSubmissionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OtoBannerClientService
{
    public function __construct(
        private readonly OtoBannerSubmissionRepository $submissionRepository,
    ) {}

    /**
     * Получить заявки по баннеру
     */
    public function getBannerSubmissions(OtoBanner $banner, int $perPage = 20): LengthAwarePaginator
    {
        return $this->submissionRepository->getByBanner($banner->id, $perPage);
    }

    /**
     * Получить все OTO заявки
     */
    public function getAllSubmissions(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->submissionRepository->getAllOtoSubmissions($perPage, $filters);
    }

    /**
     * Прикрепить клиента к сегментам баннера
     */
    public function attachClientToSegments(Client $client, array $segmentIds): void
    {
        foreach ($segmentIds as $segmentId) {
            if (!$client->isInSegment($segmentId)) {
                $client->segments()->attach($segmentId, [
                    'added_at' => now(),
                ]);
            }
        }
    }

    /**
     * Получить клиентов по баннеру
     */
    public function getBannerClients(OtoBanner $banner): \Illuminate\Support\Collection
    {
        return Client::whereHas('contactRequests', function ($query) use ($banner) {
            $query->where('oto_banner_id', $banner->id);
        })->with('profile')->get();
    }
}
