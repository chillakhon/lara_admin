<?php

namespace App\DTOs\Segment;

class SegmentExportDTO
{
    public function __construct(
        public readonly int $segmentId,
        public readonly array $columns = [
            'id',
            'full_name',
            'phone',
            'email',
            'birthday',
            'address',
            'average_check',
            'total_amount',
            'orders_count'
        ]
    ) {}

    /**
     * Создать DTO из Request
     */
    public static function fromRequest(int $segmentId, array $data): self
    {
        return new self(
            segmentId: $segmentId,
            columns: $data['columns'] ?? [
            'id',
            'full_name',
            'phone',
            'email',
            'birthday',
            'address',
            'average_check',
            'total_amount',
            'orders_count'
        ]
        );
    }

    /**
     * Получить заголовки для CSV
     */
    public function getHeaders(): array
    {
        return [
            'id' => 'ID',
            'full_name' => 'ФИО',
            'phone' => 'Телефон',
            'email' => 'Email',
            'birthday' => 'Дата рождения',
            'address' => 'Адрес',
            'average_check' => 'Средний чек',
            'total_amount' => 'Сумма покупок',
            'orders_count' => 'Количество заказов',
        ];
    }

    /**
     * Получить только выбранные заголовки
     */
    public function getSelectedHeaders(): array
    {
        $allHeaders = $this->getHeaders();
        return array_intersect_key($allHeaders, array_flip($this->columns));
    }
}
