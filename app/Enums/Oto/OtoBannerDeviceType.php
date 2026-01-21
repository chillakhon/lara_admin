<?php

namespace App\Enums\Oto;

enum OtoBannerDeviceType: string
{
    case DESKTOP = 'desktop';
    case MOBILE = 'mobile';

    public function label(): string
    {
        return match($this) {
            self::DESKTOP => 'Десктоп',
            self::MOBILE => 'Мобильный',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
