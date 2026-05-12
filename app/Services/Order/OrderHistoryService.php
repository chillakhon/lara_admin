<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;

/**
 * Сервис записи событий истории заказа.
 *
 * Все методы пишут в order_histories запись с:
 *   - action      — тип события (created/updated/deleted/item_added/item_removed)
 *   - description — человекочитаемый текст в стиле InSales
 *   - user_id     — текущий аутентифицированный пользователь (если есть)
 *   - status      — текущий status заказа (для обратной совместимости)
 */
class OrderHistoryService
{
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_ITEM_ADDED = 'item_added';
    public const ACTION_ITEM_REMOVED = 'item_removed';

    /**
     * Поля заказа, изменения которых отражаем в истории.
     * Ключ — поле в БД, значение — человекочитаемое название.
     */
    private const TRACKED_FIELDS = [
        'status' => 'Статус заказа',
        'payment_status' => 'Статус оплаты',
        'payment_method' => 'Способ оплаты',
        'total_amount' => 'Сумма заказа',
        'delivery_method_id' => 'Способ доставки',
        'delivery_cost' => 'Стоимость доставки',
        'notes' => 'Комментарий',
        'assigned_user_id' => 'Менеджер',
    ];

    public function logCreated(Order $order): void
    {
        $this->write($order, self::ACTION_CREATED, 'Заказ создан');
    }

    public function logDeleted(Order $order, ?string $reason = null): void
    {
        $description = 'Заказ удалён';
        if ($reason) {
            $description .= " (причина: {$reason})";
        }
        $this->write($order, self::ACTION_DELETED, $description);
    }

    /**
     * Сравнивает старые и новые значения, пишет по одной записи на каждое
     * изменившееся поле в стиле «X изменён с 'A' на 'B'».
     */
    public function logUpdated(Order $order, array $original, array $updated): void
    {
        foreach (self::TRACKED_FIELDS as $field => $label) {
            if (! array_key_exists($field, $updated)) {
                continue;
            }

            $oldRaw = $original[$field] ?? null;
            $newRaw = $updated[$field] ?? null;

            if ($this->valuesEqual($oldRaw, $newRaw)) {
                continue;
            }

            $oldLabel = $this->formatValue($field, $oldRaw);
            $newLabel = $this->formatValue($field, $newRaw);

            $description = "{$label} изменён с '{$oldLabel}' на '{$newLabel}'";

            $this->write($order, self::ACTION_UPDATED, $description);
        }
    }

    public function logItemAdded(Order $order, OrderItem $item): void
    {
        $name = $item->product?->name ?? "позиция #{$item->id}";
        $qty = (int) ($item->quantity ?? 0);
        $this->write($order, self::ACTION_ITEM_ADDED, "Добавлена позиция: {$name} (x{$qty})");
    }

    public function logItemRemoved(Order $order, OrderItem $item): void
    {
        $name = $item->product?->name ?? "позиция #{$item->id}";
        $this->write($order, self::ACTION_ITEM_REMOVED, "Удалена позиция: {$name}");
    }

    private function write(Order $order, string $action, string $description): void
    {
        $userId = Auth::id();

        $order->history()->create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'status' => $this->statusValue($order->status) ?? '',
            'payment_status' => $this->statusValue($order->payment_status),
            'comment' => $description,
        ]);
    }

    private function valuesEqual($a, $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }

        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }

        return $a == $b;
    }

    private function formatValue(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($field === 'status') {
            $enum = $value instanceof OrderStatus ? $value : OrderStatus::tryFrom((string) $value);
            return $enum?->label() ?? (string) $value;
        }

        if ($field === 'payment_status') {
            $enum = $value instanceof PaymentStatus ? $value : PaymentStatus::tryFrom((string) $value);
            return $enum?->label() ?? (string) $value;
        }

        if ($field === 'delivery_method_id') {
            $method = \App\Models\DeliveryMethod::find($value);
            return $method?->name ?? (string) $value;
        }

        if ($field === 'assigned_user_id') {
            $user = \App\Models\User::with('profile')->find($value);
            if (! $user) {
                return (string) $value;
            }
            return $user->get_full_name() ?: ($user->email ?? (string) $value);
        }

        if (in_array($field, ['total_amount', 'delivery_cost'], true)) {
            return number_format((float) $value, 2, '.', ' ');
        }

        return (string) $value;
    }

    private function statusValue($status): ?string
    {
        if ($status instanceof \BackedEnum) {
            return (string) $status->value;
        }

        return $status === null ? null : (string) $status;
    }
}
