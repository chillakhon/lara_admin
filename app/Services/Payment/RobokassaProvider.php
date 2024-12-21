<?php

namespace App\Services\Payment;

use App\DTOs\PaymentDTO;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;

class RobokassaProvider extends AbstractPaymentProvider
{
    public static function getProviderCode(): string
    {
        return 'robokassa';
    }

    public function initializePayment(PaymentDTO $paymentDTO): array
    {
        $payment = $this->createPayment($paymentDTO);
        
        $signature = $this->generateSignature([
            $this->config['merchant_login'],
            $paymentDTO->amount,
            $payment->id,
            $this->config['password1']
        ]);

        $paymentUrl = $this->config['payment_url'];
        
        $params = [
            'MerchantLogin' => $this->config['merchant_login'],
            'OutSum' => $paymentDTO->amount,
            'InvId' => $payment->id,
            'Description' => $paymentDTO->description,
            'SignatureValue' => $signature,
            'IsTest' => $this->config['is_test'] ? 1 : 0,
            'Email' => $paymentDTO->customer['email'],
            'Receipt' => $this->generateReceiptData($paymentDTO)
        ];

        return [
            'payment_id' => $payment->id,
            'payment_url' => $paymentUrl . '?' . http_build_query($params)
        ];
    }

    public function processPayment(array $data): Payment
    {
        $payment = Payment::findOrFail($data['InvId']);
        
        // Проверяем подпись
        $signature = $this->generateSignature([
            $data['OutSum'],
            $data['InvId'],
            $this->config['password2']
        ]);

        if ($signature !== $data['SignatureValue']) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => 'Invalid signature'
            ]);
            throw new \Exception('Invalid signature');
        }

        $payment->update([
            'status' => Payment::STATUS_COMPLETED,
            'provider_payment_id' => $data['InvId'],
            'provider_data' => $data
        ]);

        // Генерируем чек
        if ($this->receiptGenerator) {
            $receipt = $this->receiptGenerator->generateReceipt($payment);
            $this->receiptGenerator->sendReceipt($receipt);
        }

        return $payment;
    }

    public function checkPaymentStatus(Payment $payment): string
    {
        $signature = $this->generateSignature([
            $this->config['merchant_login'],
            $payment->id,
            $this->config['password2']
        ]);

        $response = $this->makeRequest('post', $this->config['status_url'], [
            'MerchantLogin' => $this->config['merchant_login'],
            'InvId' => $payment->id,
            'SignatureValue' => $signature
        ]);

        return $response['Status'] ?? Payment::STATUS_PENDING;
    }

    public function refundPayment(Payment $payment, ?float $amount = null): bool
    {
        $refundAmount = $amount ?? $payment->amount;
        
        $signature = $this->generateSignature([
            $this->config['merchant_login'],
            $refundAmount,
            $payment->id,
            $this->config['password1']
        ]);

        $response = $this->makeRequest('post', $this->config['refund_url'], [
            'MerchantLogin' => $this->config['merchant_login'],
            'OutSum' => $refundAmount,
            'InvId' => $payment->id,
            'SignatureValue' => $signature
        ]);

        if ($response['Success']) {
            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'provider_data' => array_merge(
                    $payment->provider_data ?? [], 
                    ['refund' => $response]
                )
            ]);
            return true;
        }

        return false;
    }

    protected function generateSignature(array $params): string
    {
        return md5(implode(':', $params));
    }

    protected function generateReceiptData(PaymentDTO $paymentDTO): string
    {
        $items = array_map(function($item) {
            return [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'sum' => $item['sum'],
                'tax' => $this->config['default_tax'] ?? 'none'
            ];
        }, $paymentDTO->items);

        return base64_encode(json_encode([
            'items' => $items,
            'email' => $paymentDTO->customer['email'],
            'phone' => $paymentDTO->customer['phone']
        ]));
    }
} 