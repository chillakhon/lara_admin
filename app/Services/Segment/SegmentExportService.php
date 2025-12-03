<?php

namespace App\Services\Segment;

use App\DTOs\Segment\SegmentExportDTO;
use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SegmentExportService
{
    public function __construct(
        protected SegmentRepository $repository
    ) {}

    /**
     * Экспортировать клиентов сегмента в CSV
     */
    public function exportToCSV(Segment $segment, SegmentExportDTO $dto): StreamedResponse
    {
        $fileName = $this->generateFileName($segment);

        return Response::stream(
            function () use ($segment, $dto) {
                $handle = fopen('php://output', 'w');

                // Добавляем BOM для корректного отображения кириллицы в Excel
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                // Записываем заголовки
                fputcsv($handle, array_values($dto->getSelectedHeaders()), ';');

                // Получаем клиентов сегмента порциями для экономии памяти
                $segment->clients()
                    ->with('profile')
                    ->chunk(100, function ($clients) use ($handle, $dto) {
                        foreach ($clients as $client) {
                            $row = $this->prepareClientRow($client, $dto->columns);
                            fputcsv($handle, $row, ';');
                        }
                    });

                fclose($handle);
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]
        );
    }

    /**
     * Подготовить строку данных клиента для CSV
     */
    protected function prepareClientRow($client, array $columns): array
    {
        $data = [
            'id' => $client->id,
            'full_name' => $client->profile?->full_name ?? '',
            'phone' => $client->profile?->phone ?? '',
            'email' => $client->email ?? '',
            'birthday' => $client->profile?->birthday
                ? $client->profile->birthday->format('d.m.Y')
                : '',
            'address' => $client->profile?->address ?? '',
            'average_check' => $this->calculateAverageCheck($client),
            'total_amount' => $this->calculateTotalAmount($client),
            'orders_count' => $this->calculateOrdersCount($client),
        ];

        // Возвращаем только выбранные колонки в правильном порядке
        $result = [];
        foreach ($columns as $column) {
            $result[] = $data[$column] ?? '';
        }

        return $result;
    }

    /**
     * Рассчитать средний чек клиента
     */
    protected function calculateAverageCheck($client): string
    {
        $orders = $client->orders()
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->get();

        if ($orders->isEmpty()) {
            return '0.00';
        }

        $average = $orders->avg('total_amount');
        return number_format($average, 2, '.', '');
    }

    /**
     * Рассчитать общую сумму покупок клиента
     */
    protected function calculateTotalAmount($client): string
    {
        $total = $client->orders()
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        return number_format($total, 2, '.', '');
    }

    /**
     * Рассчитать количество заказов клиента
     */
    protected function calculateOrdersCount($client): int
    {
        return $client->orders()
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->count();
    }

    /**
     * Сгенерировать имя файла для экспорта
     */
    protected function generateFileName(Segment $segment): string
    {
        $date = now()->format('Y-m-d_H-i-s');
        $segmentName = Str::slug($segment->name);

        return "segment_{$segmentName}_{$date}.csv";
    }

    /**
     * Экспортировать в массив (для других форматов)
     */
    public function exportToArray(Segment $segment, array $columns = []): array
    {
        if (empty($columns)) {
            $columns = [
                'id',
                'full_name',
                'phone',
                'email',
                'birthday',
                'address',
                'average_check',
                'total_amount',
                'orders_count'
            ];
        }

        $result = [];

        $segment->clients()
            ->with('profile')
            ->chunk(100, function ($clients) use (&$result, $columns) {
                foreach ($clients as $client) {
                    $result[] = $this->prepareClientRow($client, $columns);
                }
            });

        return $result;
    }

    /**
     * Получить количество клиентов для экспорта
     */
    public function getExportCount(Segment $segment): int
    {
        return $segment->clients()->count();
    }

    /**
     * Проверить, можно ли экспортировать сегмент
     */
    public function canExport(Segment $segment): bool
    {
        return $segment->clients()->exists();
    }

    /**
     * Получить предпросмотр экспорта (первые 10 строк)
     */
    public function getExportPreview(Segment $segment, array $columns = []): array
    {
        if (empty($columns)) {
            $columns = [
                'id',
                'full_name',
                'phone',
                'email',
                'average_check',
                'total_amount',
                'orders_count'
            ];
        }

        $clients = $segment->clients()
            ->with('profile')
            ->limit(10)
            ->get();

        $result = [];
        foreach ($clients as $client) {
            $result[] = $this->prepareClientRow($client, $columns);
        }

        return $result;
    }
}
