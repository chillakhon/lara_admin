<?php

namespace App\Services\Order;

use App\Models\Order;

/**
 * Реестр и read/write кастомных полей заказа («Поля заказа» в InSales).
 *
 * Часть полей хранится в собственных колонках orders (tracking_number,
 * no_receipt, export_country), часть — в JSON-колонке legacy_meta,
 * чтобы не плодить миграции под нишевые статусы (DMS, второй чек ККТ и т.п.).
 *
 * Сервис скрывает это разделение от внешнего кода: на чтение возвращает
 * единый список полей, на запись принимает плоский массив key => value.
 */
class OrderCustomFieldsService
{
    /**
     * Источник значений: column (на orders) или legacy_meta (json).
     */
    private const SOURCE_COLUMN = 'column';
    private const SOURCE_META = 'legacy_meta';

    /**
     * Реестр полей. Порядок важен — он же отображается на UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'key' => 'cdek_tracking_number',
                'label' => 'Номер для отслеживания заказа СДЭК',
                'type' => 'text',
                'source' => self::SOURCE_META,
                'meta_key' => 'cdek_tracking_number',
            ],
            [
                'key' => 'tracking_number',
                'label' => 'Трек-номер',
                'type' => 'text',
                'source' => self::SOURCE_COLUMN,
                'column' => 'tracking_number',
            ],
            [
                'key' => 'transaction_id',
                'label' => 'Id транзакции',
                'type' => 'text',
                'source' => self::SOURCE_META,
                'meta_key' => 'transaction_id',
            ],
            [
                'key' => 'dms_cp_statuses',
                'label' => 'Статусы DMS для CP',
                'type' => 'text',
                'source' => self::SOURCE_META,
                'meta_key' => 'dms_cp_statuses',
            ],
            [
                'key' => 'russian_post_tracking_number',
                'label' => 'Трек-номер Почта России',
                'type' => 'text',
                'source' => self::SOURCE_META,
                'meta_key' => 'russian_post_tracking_number',
            ],
            [
                'key' => 'cloudkassir_second_receipt',
                'label' => 'Второй чек прихода CloudKassir',
                'type' => 'text',
                'source' => self::SOURCE_META,
                'meta_key' => 'cloudkassir_second_receipt',
            ],
            [
                'key' => 'no_receipt',
                'label' => 'Чек не пробивать',
                'type' => 'checkbox',
                'source' => self::SOURCE_COLUMN,
                'column' => 'no_receipt',
            ],
            [
                'key' => 'export_country',
                'label' => 'Страна экспорта',
                'type' => 'select',
                'source' => self::SOURCE_COLUMN,
                'column' => 'export_country',
                'options' => [
                    ['value' => '', 'label' => '—'],
                    ['value' => 'RU', 'label' => 'Россия'],
                    ['value' => 'BY', 'label' => 'Беларусь'],
                    ['value' => 'KZ', 'label' => 'Казахстан'],
                    ['value' => 'KG', 'label' => 'Киргизия'],
                    ['value' => 'AM', 'label' => 'Армения'],
                    ['value' => 'UZ', 'label' => 'Узбекистан'],
                    ['value' => 'AZ', 'label' => 'Азербайджан'],
                    ['value' => 'TJ', 'label' => 'Таджикистан'],
                    ['value' => 'MD', 'label' => 'Молдова'],
                ],
            ],
        ];
    }

    /**
     * Возвращает поля заказа в формате для фронта: реестр + текущие значения.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forOrder(Order $order): array
    {
        $meta = is_array($order->legacy_meta) ? $order->legacy_meta : [];

        return array_map(function (array $def) use ($order, $meta) {
            $value = $this->extractValue($def, $order, $meta);

            return [
                'key' => $def['key'],
                'label' => $def['label'],
                'type' => $def['type'],
                'value' => $value,
                'options' => $def['options'] ?? null,
            ];
        }, $this->definitions());
    }

    /**
     * Применяет patch вида ['key' => 'value', ...] к заказу.
     * Сохраняет колонки и legacy_meta одним save.
     */
    public function update(Order $order, array $patch): void
    {
        if (empty($patch)) {
            return;
        }

        $defs = collect($this->definitions())->keyBy('key');
        $meta = is_array($order->legacy_meta) ? $order->legacy_meta : [];
        $metaTouched = false;
        $columnsTouched = false;

        foreach ($patch as $key => $rawValue) {
            $def = $defs->get($key);
            if (! $def) {
                continue;
            }

            $value = $this->castValue($def['type'], $rawValue);

            if ($def['source'] === self::SOURCE_COLUMN) {
                // Для текстовых/select-полей храним null вместо пустой строки.
                $order->{$def['column']} = ($def['type'] !== 'checkbox' && ($value === '' || $value === null))
                    ? null
                    : $value;
                $columnsTouched = true;
            } else {
                if ($value === null || $value === '') {
                    unset($meta[$def['meta_key']]);
                } else {
                    $meta[$def['meta_key']] = $value;
                }
                $metaTouched = true;
            }
        }

        if ($metaTouched) {
            $order->legacy_meta = $meta ?: null;
        }

        if ($metaTouched || $columnsTouched) {
            $order->save();
        }
    }

    /**
     * Достаёт текущее значение поля из заказа/мета.
     */
    private function extractValue(array $def, Order $order, array $meta): mixed
    {
        $raw = $def['source'] === self::SOURCE_COLUMN
            ? $order->{$def['column']}
            : ($meta[$def['meta_key']] ?? null);

        return match ($def['type']) {
            'checkbox' => (bool) $raw,
            default => $raw,
        };
    }

    /**
     * Приводит входное значение к типу поля.
     */
    private function castValue(string $type, mixed $raw): mixed
    {
        return match ($type) {
            'checkbox' => (bool) (is_string($raw) ? in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true) : $raw),
            'text', 'select' => $raw === null ? null : (string) $raw,
            default => $raw,
        };
    }
}
