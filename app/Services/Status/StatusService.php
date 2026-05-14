<?php

namespace App\Services\Status;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ContactRequestStatus;

class StatusService
{
    /**
     * Получить все статусы
     */
    public function getAllStatuses(): array
    {
        return [
            'order_statuses' => $this->getOrderStatuses(),
            'payment_statuses' => $this->getPaymentStatuses(),
            'contact_request_statuses' => $this->getContactRequestStatuses(),
        ];
    }

    /**
     * Получить статусы заказов
     */
    public function getOrderStatuses(): array
    {
        return OrderStatus::toArray();
    }

    /**
     * Получить статусы платежей
     */
    public function getPaymentStatuses(): array
    {
        return PaymentStatus::toArray();
    }

    /**
     * Получить статусы обращений
     */
    public function getContactRequestStatuses(): array
    {
        return ContactRequestStatus::toArray();
    }
}
