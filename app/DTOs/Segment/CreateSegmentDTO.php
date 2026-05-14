<?php

namespace App\DTOs\Segment;

class CreateSegmentDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?array $conditions,
        public readonly bool $isActive = true,
        public readonly string $recalculateFrequency = 'on_view'
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            conditions: $data['conditions'] ?? null,
            isActive: $data['is_active'] ?? true,
            recalculateFrequency: $data['recalculate_frequency'] ?? 'on_view'
        );
    }

    /**
     * Преобразовать DTO в массив для создания модели
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'conditions' => $this->conditions,
            'is_active' => $this->isActive,
            'recalculate_frequency' => $this->recalculateFrequency,
        ];
    }
}
