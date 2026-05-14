<?php

namespace App\DTOs\Segment;

class SegmentStatisticsDTO
{
    public function __construct(
        public readonly int $clientsCount,
        public readonly float $totalAmount,
        public readonly float $averageCheck,
        public readonly int $totalOrders,
        public readonly array $clientsBreakdown = []
    ) {}

    /**
     * Создать DTO из данных статистики
     */
    public static function fromData(
        int $clientsCount,
        float $totalAmount,
        float $averageCheck,
        int $totalOrders,
        array $clientsBreakdown = []
    ): self {
        return new self(
            clientsCount: $clientsCount,
            totalAmount: $totalAmount,
            averageCheck: $averageCheck,
            totalOrders: $totalOrders,
            clientsBreakdown: $clientsBreakdown
        );
    }

    /**
     * Преобразовать в массив для API ответа
     */
    public function toArray(): array
    {
        return [
            'clients_count' => $this->clientsCount,
            'total_amount' => round($this->totalAmount, 2),
            'average_check' => round($this->averageCheck, 2),
            'total_orders' => $this->totalOrders,
            'clients_breakdown' => $this->clientsBreakdown,
        ];
    }
}
