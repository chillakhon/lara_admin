<?php

namespace App\Enums\Oto;

enum OtoBannerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Активен',
            self::INACTIVE => 'Неактивен',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
