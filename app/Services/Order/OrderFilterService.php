<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Reverb\Loggers\Log;

class OrderFilterService
{
    /**
     * Применить фильтры к запросу заказов
     */
    public function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('status')) {
            $query = $this->filterByStatus($query, $request->status);
        }

        if ($request->filled('payment_status')) {
            $query = $this->filterByPaymentStatus($query, $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query = $this->filterByDateFrom($query, $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query = $this->filterByDateTo($query, $request->date_to);
        }

        if ($request->filled('client_id')) {
            $query = $this->filterByClientId($query, $request->client_id);
        }

        if ($request->filled('promo_code')) {
            $query = $this->filterByPromoCode($query, $request->promo_code);
        }

        if ($request->filled('city')) {
            $query = $this->filterByCity($query, $request->city);
        }

        if ($request->filled('country_code')) {
            $query = $this->filterByCountry($query, $request->country_code);
        }

        if ($request->filled('phone')) {
            $query = $this->filterByPhone($query, $request->phone);
        }

        if ($request->filled('min_amount')) {
            $query = $this->filterByMinAmount($query, $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query = $this->filterByMaxAmount($query, $request->max_amount);
        }

        if ($request->filled('search')) {
            $query = $this->search($query, $request->search);
        }

        return $query;
    }

    /**
     * Фильтр по статусу заказа
     */
    private function filterByStatus(Builder $query, string $status): Builder
    {
        $allowedStatuses = Order::getStatusValues();

        if (in_array($status, $allowedStatuses)) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Фильтр по статусу оплаты
     */
    private function filterByPaymentStatus(Builder $query, string $paymentStatus): Builder
    {
        $allowedStatuses = Order::getPaymentStatusValues();

        if (in_array($paymentStatus, $allowedStatuses)) {
            return $query->where('payment_status', $paymentStatus);
        }

        return $query;
    }

    /**
     * Фильтр по дате создания (от)
     */
    private function filterByDateFrom(Builder $query, string $dateFrom): Builder
    {
        try {
            $date = \Carbon\Carbon::parse($dateFrom);
            return $query->whereDate('created_at', '>=', $date);
        } catch (\Exception $e) {
            return $query;
        }
    }

    /**
     * Фильтр по дате создания (до)
     */
    private function filterByDateTo(Builder $query, string $dateTo): Builder
    {
        try {
            $date = \Carbon\Carbon::parse($dateTo);
            return $query->whereDate('created_at', '<=', $date);
        } catch (\Exception $e) {
            return $query;
        }
    }

    /**
     * Фильтр по ID клиента
     */
    private function filterByClientId(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Фильтр по промокоду
     */
    private function filterByPromoCode(Builder $query, string $promoCode): Builder
    {
        return $query->whereHas('promoCode', function ($q) use ($promoCode) {
            $q->where('code', 'like', '%' . $promoCode . '%');
        });
    }

    /**
     * Фильтр по городу
     */
    private function filterByCity(Builder $query, string $city): Builder
    {
        return $query->where('city_name', 'like', '%' . $city . '%');
    }

    /**
     * Фильтр по стране
     */
    private function filterByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Фильтр по телефону (безопасная версия с проверкой связей)
     */
    private function filterByPhone(Builder $query, string $phone): Builder
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        return $query->where(function ($q) use ($cleanPhone) {
            // Поиск в client.profile
            $q->whereHas('client', function ($clientQuery) use ($cleanPhone) {
                $clientQuery->whereHas('profile', function ($profileQuery) use ($cleanPhone) {
                    $profileQuery->where('phone', 'like', '%' . $cleanPhone . '%');
                });
            });

            // Или поиск напрямую в orders, если телефон там тоже хранится
            if (\Schema::hasColumn('orders', 'phone')) {
                $q->orWhere('orders.phone', 'like', '%' . $cleanPhone . '%');
            }
        });
    }

    /**
     * Фильтр по минимальной сумме заказа
     */
    private function filterByMinAmount(Builder $query, float $minAmount): Builder
    {
        return $query->where('total_amount', '>=', $minAmount);
    }

    /**
     * Фильтр по максимальной сумме заказа
     */
    private function filterByMaxAmount(Builder $query, float $maxAmount): Builder
    {
        return $query->where('total_amount', '<=', $maxAmount);
    }

    /**
     * Поиск по тексту (для user_profiles, а не client_profiles)
     */
    private function search(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            // Поиск по ID заказа
            if (is_numeric($search)) {
                $q->orWhere('orders.id', $search);
            }

            // Поиск по данным клиента в profile (user_profiles)
            $q->orWhereHas('client', function ($clientQuery) use ($search) {
                $clientQuery->whereHas('profile', function ($profileQuery) use ($search) {
                    // Поиск по имени
                    $profileQuery->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ['%' . $search . '%']);

                    // Поиск по телефону
                    $cleanSearch = preg_replace('/[^0-9]/', '', $search);
                    if (!empty($cleanSearch)) {
                        $profileQuery->orWhere('phone', 'like', '%' . $cleanSearch . '%');
                    }

                    // Поиск по адресу
                    $profileQuery->orWhere('address', 'like', '%' . $search . '%');
                });
            });

            // Поиск по другим полям в orders
            if (\Schema::hasColumn('orders', 'notes')) {
                $q->orWhere('orders.notes', 'like', '%' . $search . '%');
            }
        });
    }

    /**
     * Применить сортировку
     */
    public function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Разрешенные поля для сортировки
        $allowedSortFields = [
            'id',
            'created_at',
            'total_amount',
            'status',
            'payment_status',
            'city_name',
            'country_code',
        ];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Получить параметры фильтрации для отображения
     */
    public function getActiveFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('status')) {
            $filters['status'] = [
                'label' => 'Статус',
                'value' => $request->status,
            ];
        }

        if ($request->filled('payment_status')) {
            $filters['payment_status'] = [
                'label' => 'Статус оплаты',
                'value' => $request->payment_status,
            ];
        }

        if ($request->filled('date_from')) {
            $filters['date_from'] = [
                'label' => 'Дата от',
                'value' => $request->date_from,
            ];
        }

        if ($request->filled('date_to')) {
            $filters['date_to'] = [
                'label' => 'Дата до',
                'value' => $request->date_to,
            ];
        }

        if ($request->filled('client_id')) {
            $filters['client_id'] = [
                'label' => 'ID клиента',
                'value' => $request->client_id,
            ];
        }

        if ($request->filled('promo_code')) {
            $filters['promo_code'] = [
                'label' => 'Промокод',
                'value' => $request->promo_code,
            ];
        }

        if ($request->filled('city')) {
            $filters['city'] = [
                'label' => 'Город',
                'value' => $request->city,
            ];
        }

        if ($request->filled('country_code')) {
            $filters['country_code'] = [
                'label' => 'Страна',
                'value' => $request->country_code,
            ];
        }

        if ($request->filled('min_amount')) {
            $filters['min_amount'] = [
                'label' => 'Мин. сумма',
                'value' => $request->min_amount,
            ];
        }

        if ($request->filled('max_amount')) {
            $filters['max_amount'] = [
                'label' => 'Макс. сумма',
                'value' => $request->max_amount,
            ];
        }

        if ($request->filled('search')) {
            $filters['search'] = [
                'label' => 'Поиск',
                'value' => $request->search,
            ];
        }

        return $filters;
    }

    /**
     * Валидация параметров фильтрации
     */
    public function validateFilterParams(Request $request): array
    {
        return $request->validate([
//            'status' => 'nullable|string|in:pending,processing,confirmed,shipped,delivered,cancelled',
            'status' => 'nullable|string',
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'client_id' => 'nullable|integer|exists:clients,id',
            'promo_code' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|size:2',
            'phone' => 'nullable|string|max:20',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|string|in:id,created_at,total_amount,status,payment_status,city_name,country_code',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }
}
