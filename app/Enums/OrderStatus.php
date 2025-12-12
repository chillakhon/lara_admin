<?php

namespace App\Enums;

enum OrderStatus: string
{
    case NEW = 'new';
    case PROCESSING = 'processing';
    case ASSEMBLED = 'assembled';
    case TSD_CONTROL = 'tsd_control';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CHECK_ISSUED = 'check_issued';
    case CANCELLED = 'cancelled';
    case CLAIM = 'claim';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Новый',
            self::PROCESSING => 'В работе',
            self::ASSEMBLED => 'Собран',
            self::TSD_CONTROL => 'На контроле в ТСД',
            self::SHIPPED => 'Отгружен',
            self::DELIVERED => 'Доставлен',
            self::CHECK_ISSUED => 'Выдан чек (ферма)',
            self::CANCELLED => 'Отменен',
            self::CLAIM => 'Рекламация',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => '#fb7878',
            self::PROCESSING => '#efd49c',
            self::ASSEMBLED => '#558f5a',
            self::TSD_CONTROL => '#7391ec',
            self::SHIPPED => '#7391ec',
            self::DELIVERED => '#6fbaba',
            self::CHECK_ISSUED => '#10B981',
            self::CANCELLED => '#f88686',
            self::CLAIM => '#ff7d54',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // Для отдачи фронту
    public static function toArray(): array
    {
        $result = [];
        foreach (self::cases() as $status) {
            $result[$status->value] = [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ];
        }
        return $result;
    }
}
