<?php

namespace App\Services\GiftCard;



use App\Models\GiftCard\GiftCard;

class GiftCardCodeGenerator
{
    /**
     * Генерация уникального кода подарочной карты
     * Формат: 12 символов (71SA7DD7GT12)
     */
    public function generate(): string
    {
        do {
            $code = $this->generateCode();
        } while ($this->codeExists($code));

        return $code;
    }

    /**
     * Генерация кода из букв и цифр
     */
    private function generateCode(): string
    {
        // Используем только заглавные буквы и цифры, исключая похожие символы (0, O, I, 1)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < 12; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }

    /**
     * Проверка существования кода
     */
    private function codeExists(string $code): bool
    {
        return GiftCard::where('code', $code)->exists();
    }

    /**
     * Генерация красивого кода с разделителями (для отображения)
     * Пример: 71SA-7DD7-GT12
     */
    public function formatCode(string $code): string
    {
        return chunk_split($code, 4, '-');
    }

    /**
     * Очистка кода от разделителей
     */
    public function cleanCode(string $code): string
    {
        return strtoupper(str_replace(['-', ' '], '', $code));
    }
}
