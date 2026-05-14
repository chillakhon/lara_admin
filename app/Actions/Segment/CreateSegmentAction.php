<?php

namespace App\Actions\Segment;

use App\DTOs\Segment\CreateSegmentDTO;
use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use Illuminate\Support\Facades\DB;

class CreateSegmentAction
{
    public function __construct(
        protected SegmentRepository $repository
    ) {}

    /**
     * Выполнить создание сегмента
     */
    public function execute(CreateSegmentDTO $dto): Segment
    {
        // Проверяем уникальность имени
        if ($this->repository->existsByName($dto->name)) {
            throw new \InvalidArgumentException("Сегмент с именем '{$dto->name}' уже существует");
        }

        return DB::transaction(function () use ($dto) {
            return $this->repository->create($dto->toArray());
        });
    }
}
