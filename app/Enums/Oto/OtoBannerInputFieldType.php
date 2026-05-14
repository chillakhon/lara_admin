<?php

namespace App\Enums\Oto;

enum OtoBannerInputFieldType: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case TEXT = 'text';

    public function label(): string
    {
        return match($this) {
            self::EMAIL => 'Email',
            self::PHONE => 'Телефон',
            self::TEXT => 'Текст',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function placeholder(): string
    {
        return match($this) {
            self::EMAIL => 'Введите ваш email',
            self::PHONE => 'Введите ваш телефон',
            self::TEXT => 'Введите текст',
        };
    }

    public function validationRule(): string
    {
        return match($this) {
            self::EMAIL => 'email',
            self::PHONE => 'string',
            self::TEXT => 'string',
        };
    }
}
