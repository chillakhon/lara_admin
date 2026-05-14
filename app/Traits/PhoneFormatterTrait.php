<?php

namespace App\Traits;

trait PhoneFormatterTrait
{
    /**
     * Форматировать номер телефона для WhatsApp
     */
    public function formatPhoneForWhatsApp(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Удаляем все символы кроме цифр
        $phone = preg_replace('/\D/', '', $phone);

        // Если номер начинается с 8 (старый формат России) - заменяем на 7
        if (strlen($phone) === 11 && str_starts_with($phone, '8')) {
            $phone = '7' . substr($phone, 1);
        }

        // Если номер 10 цифр - добавляем 7 (Россия)
        if (strlen($phone) === 10) {
            $phone = '7' . $phone;
        }

        // Добавляем + в начало
        return '+' . $phone;
    }
}
