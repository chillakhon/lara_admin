<?php

namespace App\Services\Export;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class ExportService
{
    /**
     * Размер chunk для обработки данных
     */
    protected int $chunkSize;

    /**
     * Разделитель CSV
     */
    protected string $delimiter = ';';

    /**
     * Кодировка файла
     */
    protected string $encoding = 'UTF-8';

    public function __construct(int $chunkSize = 1000)
    {
        $this->chunkSize = $chunkSize;
    }

    /**
     * Получить заголовки CSV
     */
    abstract protected function getHeaders(): array;

    /**
     * Форматировать строку данных для CSV
     */
    abstract protected function formatRow($item): array;

    /**
     * Получить query builder для выборки данных
     */
    abstract protected function getQuery(array $ids = []);

    /**
     * Генерировать имя файла
     */
    abstract protected function getFileName(): string;

    /**
     * Экспорт данных в CSV
     */
    public function export(array $ids = []): StreamedResponse
    {
        $fileName = $this->getFileName();

        return Response::stream(function () use ($ids) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM для корректного открытия в Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Записываем заголовки
            fputcsv($handle, $this->getHeaders(), $this->delimiter);

            // Получаем query и обрабатываем данные чанками
            $query = $this->getQuery($ids);

            $query->chunk($this->chunkSize, function ($items) use ($handle) {
                foreach ($items as $item) {
                    fputcsv($handle, $this->formatRow($item), $this->delimiter);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
