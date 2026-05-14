<?php

namespace App\DTOs\Segment;

class SegmentConditionsDTO
{
    public function __construct(
        public readonly ?string $period = null,
        public readonly ?int $minOrdersCount = null,
        public readonly ?int $maxOrdersCount = null,
        public readonly ?float $minTotalAmount = null,
        public readonly ?float $minAverageCheck = null
    ) {}

    /**
     * Создать DTO из массива условий
     */
    public static function fromArray(?array $conditions): ?self
    {
        if (empty($conditions)) {
            return null;
        }

        return new self(
            period: $conditions['period'] ?? null,
            minOrdersCount: isset($conditions['min_orders_count'])
                ? (int) $conditions['min_orders_count']
                : null,
            maxOrdersCount: isset($conditions['max_orders_count'])
                ? (int) $conditions['max_orders_count']
                : null,
            minTotalAmount: isset($conditions['min_total_amount'])
                ? (float) $conditions['min_total_amount']
                : null,
            minAverageCheck: isset($conditions['min_average_check'])
                ? (float) $conditions['min_average_check']
                : null
        );
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return array_filter([
            'period' => $this->period,
            'min_orders_count' => $this->minOrdersCount,
            'max_orders_count' => $this->maxOrdersCount,
            'min_total_amount' => $this->minTotalAmount,
            'min_average_check' => $this->minAverageCheck,
        ], fn($value) => $value !== null);
    }

    /**
     * Проверить, есть ли хотя бы одно условие
     */
    public function hasConditions(): bool
    {
        return $this->period !== null
            || $this->minOrdersCount !== null
            || $this->maxOrdersCount !== null
            || $this->minTotalAmount !== null
            || $this->minAverageCheck !== null;
    }

    /**
     * Получить дату начала периода
     */
    public function getStartDate(): ?\Carbon\Carbon
    {
        return match ($this->period) {
            'last_month' => now()->subMonth(),
            'last_6_months' => now()->subMonths(6),
            'last_year' => now()->subYear(),
            'all_time' => null,
            default => null,
        };
    }
}
