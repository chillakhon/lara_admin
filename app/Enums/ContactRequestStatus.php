<?php

namespace App\Enums;

enum ContactRequestStatus: string
{
    case NEW = 'new';
    case VIEWED = 'viewed';
    case PROCESSED = 'processed';

    public function label(): string
    {
        return match($this) {
            self::NEW => 'Новая',
            self::VIEWED => 'Просмотрена',
            self::PROCESSED => 'Обработана',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NEW => '#ff3d3d',
            self::VIEWED => '#ffb126',
            self::PROCESSED => '#00ba15',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

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
