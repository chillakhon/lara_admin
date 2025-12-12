<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает оплаты',
            self::PAID => 'Оплачен',
            self::FAILED => 'Ошибка оплаты',
            self::REFUNDED => 'Возврат',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => '#979797',
            self::PAID => '#10B981',
            self::FAILED => '#ec5353',
            self::REFUNDED => '#f1ad41',
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
