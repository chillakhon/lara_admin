<?php

namespace App\Http\Resources\Segment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SegmentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'conditions' => $this->formatConditions(),
            'is_active' => $this->is_active,
            'recalculate_frequency' => $this->recalculate_frequency,
            'last_recalculated_at' => $this->last_recalculated_at?->format('d.m.Y H:i:s'),
            'clients_count' => $this->clients()->count(),
            'created_at' => $this->created_at->format('d.m.Y H:i:s'),
            'updated_at' => $this->updated_at->format('d.m.Y H:i:s'),

            // Связи
            'promo_codes' => PromoCodeBriefResource::collection($this->whenLoaded('promoCodes')),

            // Статистика (если передана)
            'statistics' => $this->when(isset($this->statistics), $this->statistics),
        ];
    }

    /**
     * Форматировать условия для удобного отображения
     */
    protected function formatConditions(): ?array
    {
        if (!$this->conditions) {
            return null;
        }

        $conditions = $this->conditions;
        $formatted = [];

        // Период
        if (isset($conditions['period'])) {
            $formatted['period'] = [
                'value' => $conditions['period'],
                'label' => $this->getPeriodLabel($conditions['period'])
            ];
        }

        // Количество заказов
        if (isset($conditions['min_orders_count']) || isset($conditions['max_orders_count'])) {
            $formatted['orders_count'] = [
                'min' => $conditions['min_orders_count'] ?? null,
                'max' => $conditions['max_orders_count'] ?? null,
                'label' => $this->getOrdersCountLabel($conditions)
            ];
        }

        // Сумма покупок
        if (isset($conditions['min_total_amount'])) {
            $formatted['total_amount'] = [
                'min' => $conditions['min_total_amount'],
                'label' => 'Сумма покупок >= ' . number_format($conditions['min_total_amount'], 2) . '₽'
            ];
        }

        // Средний чек
        if (isset($conditions['min_average_check'])) {
            $formatted['average_check'] = [
                'min' => $conditions['min_average_check'],
                'label' => 'Средний чек >= ' . number_format($conditions['min_average_check'], 2) . '₽'
            ];
        }

        return $formatted;
    }

    /**
     * Получить название периода
     */
    protected function getPeriodLabel(string $period): string
    {
        return match ($period) {
            'all_time' => 'За всё время',
            'last_month' => 'За последний месяц',
            'last_6_months' => 'За последние 6 месяцев',
            'last_year' => 'За последний год',
            default => $period,
        };
    }

    /**
     * Получить описание условия по количеству заказов
     */
    protected function getOrdersCountLabel(array $conditions): string
    {
        $min = $conditions['min_orders_count'] ?? null;
        $max = $conditions['max_orders_count'] ?? null;

        if ($min && $max) {
            return "От {$min} до {$max} заказов";
        } elseif ($min) {
            return "Минимум {$min} заказов";
        } elseif ($max) {
            return "Максимум {$max} заказов";
        }

        return '';
    }
}
