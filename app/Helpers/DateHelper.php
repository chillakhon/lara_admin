<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Форматирует дату в русский формат (DD.MM.YYYY)
     *
     * @param string|null $date
     * @param bool $withTime Включить время (DD.MM.YYYY HH:MM)
     * @return string
     */
    public static function formatRussian($date, bool $withTime = false): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            $carbonDate = Carbon::parse($date);
            return $withTime
                ? $carbonDate->format('d.m.Y H:i')
                : $carbonDate->format('d.m.Y');
        } catch (\Exception $e) {
            return '';
        }
    }
}
