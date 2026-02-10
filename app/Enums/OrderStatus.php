<?php

namespace App\Enums;

enum OrderStatus: string
{
    case NEW = 'new';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case SHIPPED_EXPORT = 'shipped_export';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case PRODUCT_RETURN = 'product_return';

//    case ASSEMBLED = 'assembled';
//    case TSD_CONTROL = 'tsd_control';
//    case CHECK_ISSUED = 'check_issued';
//    case CLAIM = 'claim';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Новый',
            self::PROCESSING => 'В работе',
            self::SHIPPED => 'Отгружен',
            self::SHIPPED_EXPORT => 'Отгружен на экспорт',
            self::DELIVERED => 'Доставлен',
            self::CANCELLED => 'Отменен',
            self::PRODUCT_RETURN => 'Возврат товара',


//            self::ASSEMBLED => 'Собран',
//            self::TSD_CONTROL => 'На контроле в ТСД',
//            self::CHECK_ISSUED => 'Выдан чек (ферма)',
//            self::CLAIM => 'Рекламация',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => '#fb7878',
            self::PROCESSING => '#efd49c',
            self::SHIPPED => '#7391ec',
            self::SHIPPED_EXPORT => '#9333ea', // фиолетовый для экспорта
            self::DELIVERED => '#6fbaba',
            self::CANCELLED => '#f88686',
            self::PRODUCT_RETURN => '#f59e0b', // оранжевый для возврата


//            self::ASSEMBLED => '#558f5a',
//            self::TSD_CONTROL => '#7391ec',
//            self::CHECK_ISSUED => '#10B981',
//            self::CLAIM => '#ff7d54',
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
