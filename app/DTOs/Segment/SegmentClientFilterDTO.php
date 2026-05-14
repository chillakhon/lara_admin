<?php

namespace App\DTOs\Segment;

class SegmentClientFilterDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $periodFrom = null,
        public readonly ?string $periodTo = null,
        public readonly ?float $minTotalAmount = null,
        public readonly ?float $maxTotalAmount = null,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}

    /**
     * Создать DTO из Request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            periodFrom: $data['period_from'] ?? null,
            periodTo: $data['period_to'] ?? null,
            minTotalAmount: isset($data['min_total_amount'])
                ? (float) $data['min_total_amount']
                : null,
            maxTotalAmount: isset($data['max_total_amount'])
                ? (float) $data['max_total_amount']
                : null,
            perPage: (int) ($data['per_page'] ?? 15),
            sortBy: $data['sort_by'] ?? 'created_at',
            sortDirection: $data['sort_direction'] ?? 'desc'
        );
    }

    /**
     * Проверить, есть ли активные фильтры
     */
    public function hasFilters(): bool
    {
        return $this->search !== null
            || $this->periodFrom !== null
            || $this->periodTo !== null
            || $this->minTotalAmount !== null
            || $this->maxTotalAmount !== null;
    }
}
