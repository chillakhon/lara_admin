<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Форматирует число в русский формат (1 234,56)
     *
     * @param float|int|null $number
     * @param int $decimals Количество знаков после запятой
     * @return string
     */
    public static function formatRussian($number, int $decimals = 2): string
    {
        if ($number === null || $number === '') {
            return '';
        }

        // number_format: тысячи разделяем пробелом, дробную часть запятой
        return number_format((float)$number, $decimals, ',', ' ');
    }
}
