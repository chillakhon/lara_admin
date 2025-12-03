<?php

namespace App\DTOs\Segment;

class UpdateSegmentDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?array $conditions = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $recalculateFrequency = null
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            conditions: $data['conditions'] ?? null,
            isActive: $data['is_active'] ?? null,
            recalculateFrequency: $data['recalculate_frequency'] ?? null
        );
    }

    /**
     * Преобразовать DTO в массив (только заполненные поля)
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'conditions' => $this->conditions,
            'is_active' => $this->isActive,
            'recalculate_frequency' => $this->recalculateFrequency,
        ], fn($value) => $value !== null);
    }
}
