<?php

namespace App\Services\Export;

use App\Helpers\DateHelper;
use App\Helpers\NumberHelper;
use App\Models\Review\Review;
use Illuminate\Database\Eloquent\Builder;

class ReviewExportService extends ExportService
{
    /**
     * Получить заголовки CSV
     */
    protected function getHeaders(): array
    {
        return [
            'ID',
            'Клиент',
            'Email клиента',
            'Товар/Сущность',
            'Тип сущности',
            'Рейтинг',
            'Текст отзыва',
            'Статус',
            'Опубликован',
            'Верифицирован',
            'Дата создания',
            'Дата публикации',
            'Количество ответов',
            'Количество лайков',
            'Ответы',
        ];
    }

    /**
     * Форматировать строку данных для CSV
     */
    protected function formatRow($review): array
    {
        // Формируем список ответов
        $responses = $review->responses
            ->pluck('content')
            ->map(function($content) {
                // Удаляем переносы строк и лишние пробелы
                return trim(preg_replace('/\s+/', ' ', $content));
            })
            ->implode(' | '); // Разделитель между ответами

        // Получаем название товара/сущности
        $reviewableName = '';
        if ($review->reviewable) {
            $reviewableName = $review->reviewable->name ??
                $review->reviewable->title ??
                'ID: ' . $review->reviewable_id;
        }

        // Преобразуем тип сущности в читаемый формат
        $reviewableType = $this->getReadableType($review->reviewable_type);

        // Статус
        $status = $review->status === Review::STATUS_PUBLISHED ? 'Опубликован' : 'Новый';

        return [
            $review->id,
            $review->client->profile->full_name ?? '',
            $review->client->email ?? '',
            $reviewableName,
            $reviewableType,
            $review->rating ?? 0,
            $review->content ?? '',
            $status,
            $review->is_published ? 'Да' : 'Нет',
            $review->is_verified ? 'Да' : 'Нет',
            DateHelper::formatRussian($review->created_at),
            DateHelper::formatRussian($review->published_at),
            $review->responses_count ?? 0,
            $review->likes_count ?? 0,
            $responses,
        ];
    }

    /**
     * Получить query builder для выборки данных
     */
    protected function getQuery(array $ids = []): Builder
    {
        $query = Review::query()
            ->with([
                'client.profile',
                'reviewable',
                'responses' => function($query) {
                    $query->whereNull('deleted_at');
                }
            ])
            ->withCount(['responses', 'likes'])
            ->whereNull('deleted_at');

        // Если переданы конкретные ID - фильтруем
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        // Сортировка как в таблице (latest)
        $query->latest();

        return $query;
    }

    /**
     * Генерировать имя файла
     */
    protected function getFileName(): string
    {
        $timestamp = now()->format('Ymd_His');
        return "reviews_{$timestamp}.csv";
    }

    /**
     * Преобразовать класс модели в читаемый тип
     */
    private function getReadableType(string $modelClass): string
    {
        $types = [
            'App\Models\Product' => 'Товар',
            'App\Models\Service' => 'Услуга',
            // Добавь другие типы если есть
        ];

        return $types[$modelClass] ?? class_basename($modelClass);
    }
}
