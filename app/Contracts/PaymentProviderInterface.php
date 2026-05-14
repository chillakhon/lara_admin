<?php

namespace App\Contracts;

use App\DTOs\PaymentDTO;
use App\Models\Payment;

interface PaymentProviderInterface
{
    /**
     * Инициализация платежа
     */
    public function initializePayment(PaymentDTO $paymentDTO): array;
    
    /**
     * Обработка платежа
     */
    public function processPayment(array $data): Payment;
    
    /**
     * Проверка статуса платежа
     */
    public function checkPaymentStatus(Payment $payment): string;
    
    /**
     * Возврат платежа
     */
    public function refundPayment(Payment $payment, ?float $amount = null): bool;
} 