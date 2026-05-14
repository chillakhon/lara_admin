<?php

namespace App\Contracts;

use App\DTOs\ReceiptDTO;
use App\Models\Payment;

interface ReceiptGeneratorInterface
{
    /**
     * Генерация чека
     */
    public function generateReceipt(Payment $payment): ReceiptDTO;
    
    /**
     * Отправка чека
     */
    public function sendReceipt(ReceiptDTO $receipt): bool;
    
    /**
     * Проверка статуса чека
     */
    public function checkReceiptStatus(string $receiptNumber): string;
} 