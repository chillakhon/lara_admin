<?php

namespace App\Services\Segment;

use App\DTOs\Segment\SegmentStatisticsDTO;
use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;

class SegmentStatisticsService
{
    public function __construct(
        protected SegmentRepository $repository
    ) {}

    /**
     * Получить общую статистику сегмента
     */
    public function getStatistics(Segment $segment): SegmentStatisticsDTO
    {
        $stats = $this->repository->getSegmentStatistics($segment);
        $breakdown = $this->repository->getClientsBreakdown($segment);

        return SegmentStatisticsDTO::fromData(
            clientsCount: $stats['clients_count'],
            totalAmount: $stats['total_amount'],
            averageCheck: $stats['average_check'],
            totalOrders: $stats['total_orders'],
            clientsBreakdown: $breakdown
        );
    }

    /**
     * Получить краткую статистику для списка сегментов
     */
    public function getBriefStatistics(Segment $segment): array
    {
        $stats = $this->repository->getSegmentStatistics($segment);

        return [
            'clients_count' => $stats['clients_count'],
            'total_amount' => round($stats['total_amount'], 2),
            'average_check' => round($stats['average_check'], 2),
            'total_orders' => $stats['total_orders'],
        ];
    }

    /**
     * Получить статистику для всех сегментов
     */
    public function getAllSegmentsStatistics(): array
    {
        $segments = $this->repository->getAll(['is_active' => true]);
        $result = [];

        foreach ($segments as $segment) {
            $result[] = [
                'segment_id' => $segment->id,
                'segment_name' => $segment->name,
                'statistics' => $this->getBriefStatistics($segment),
            ];
        }

        return $result;
    }

    /**
     * Сравнить статистику нескольких сегментов
     */
    public function compareSegments(array $segmentIds): array
    {
        $result = [];

        foreach ($segmentIds as $segmentId) {
            $segment = $this->repository->findById($segmentId);
            $stats = $this->repository->getSegmentStatistics($segment);

            $result[] = [
                'segment_id' => $segment->id,
                'segment_name' => $segment->name,
                'clients_count' => $stats['clients_count'],
                'total_amount' => round($stats['total_amount'], 2),
                'average_check' => round($stats['average_check'], 2),
                'total_orders' => $stats['total_orders'],
            ];
        }

        return $result;
    }

    /**
     * Получить топ сегментов по количеству клиентов
     */
    public function getTopByClients(int $limit = 5): array
    {
        $segments = $this->repository->getAll();

        $segmentsWithStats = $segments->map(function ($segment) {
            $stats = $this->repository->getSegmentStatistics($segment);
            return [
                'segment_id' => $segment->id,
                'segment_name' => $segment->name,
                'clients_count' => $stats['clients_count'],
                'total_amount' => round($stats['total_amount'], 2),
            ];
        });

        return $segmentsWithStats
            ->sortByDesc('clients_count')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Получить топ сегментов по сумме покупок
     */
    public function getTopByRevenue(int $limit = 5): array
    {
        $segments = $this->repository->getAll();

        $segmentsWithStats = $segments->map(function ($segment) {
            $stats = $this->repository->getSegmentStatistics($segment);
            return [
                'segment_id' => $segment->id,
                'segment_name' => $segment->name,
                'clients_count' => $stats['clients_count'],
                'total_amount' => round($stats['total_amount'], 2),
            ];
        });

        return $segmentsWithStats
            ->sortByDesc('total_amount')
            ->take($limit)
            ->values()
            ->toArray();
    }
}
