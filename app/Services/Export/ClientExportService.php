<?php

namespace App\Services\Export;

use App\Helpers\DateHelper;
use App\Helpers\NumberHelper;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

class ClientExportService extends ExportService
{
    /**
     * Получить заголовки CSV
     */
    protected function getHeaders(): array
    {
        return [
            'ID',
            'Email',
            'Имя',
            'Фамилия',
            'Телефон',
            'День рождения',
            'Адрес',
            'Дата регистрации',
            'Количество заказов',
            'Баланс бонусов',
        ];
    }

    /**
     * Форматировать строку данных для CSV
     */
    protected function formatRow($client): array
    {
        return [
            $client->id,
            $client->email ?? '',
            $client->profile->first_name ?? '',
            $client->profile->last_name ?? '',
            $client->profile->phone ?? '',
            DateHelper::formatRussian($client->profile->birthday ?? null),
            $client->profile->address ?? '',
            DateHelper::formatRussian($client->created_at),
            $client->orders_count ?? 0,
            NumberHelper::formatRussian($client->bonus_balance ?? 0),
        ];
    }

    /**
     * Получить query builder для выборки данных
     */
    protected function getQuery(array $ids = []): Builder
    {
        $query = Client::query()
            ->with(['profile'])
            ->withCount('orders')
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
        return "clients_{$timestamp}.csv";
    }
}
