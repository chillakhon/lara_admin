<?php

namespace App\Actions\Segment;

use App\DTOs\Segment\UpdateSegmentDTO;
use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use Illuminate\Support\Facades\DB;

class UpdateSegmentAction
{
    public function __construct(
        protected SegmentRepository $repository
    ) {}

    /**
     * Выполнить обновление сегмента
     */
    public function execute(Segment $segment, UpdateSegmentDTO $dto): Segment
    {
        // Если меняется имя, проверяем уникальность
        if ($dto->name && $dto->name !== $segment->name) {
            if ($this->repository->existsByName($dto->name, $segment->id)) {
                throw new \InvalidArgumentException("Сегмент с именем '{$dto->name}' уже существует");
            }
        }

        return DB::transaction(function () use ($segment, $dto) {
            return $this->repository->update($segment, $dto->toArray());
        });
    }
}
