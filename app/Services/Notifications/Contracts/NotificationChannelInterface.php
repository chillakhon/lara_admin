<?php

namespace App\Services\Notifications\Contracts;

interface NotificationChannelInterface
{
    /**
     * Отправить уведомление через канал
     *
     * @param string $recipientId - ID получателя в этом канале (email, phone, telegram_id и т.д.)
     * @param string $message - Текст сообщения
     * @param array $data - Дополнительные данные (заказ, клиент и т.д.)
     * @return bool - Успешно ли отправлено
     */
    public function send(string $recipientId, string $message, array $data = []): bool;

    /**
     * Получить название канала
     */
    public function getChannelName(): string;
}
