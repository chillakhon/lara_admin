<?php

namespace App\Services\Payment;

use App\DTOs\PaymentDTO;
use App\Models\Payment;
use Illuminate\Support\Str;

class YandexPayProvider extends AbstractPaymentProvider
{
    public static function getProviderCode(): string
    {
        return 'yandexpay';
    }

    public function initializePayment(PaymentDTO $paymentDTO): array
    {
        $payment = $this->createPayment($paymentDTO);
        
        $orderData = [
            'merchant_id' => $this->config['merchant_id'],
            'order_id' => $payment->id,
            'amount' => [
                'value' => $paymentDTO->amount,
                'currency' => $paymentDTO->currency
            ],
            'capture_method' => 'MANUAL',
            'description' => $paymentDTO->description,
            'merchant_order_id' => $payment->order->order_number,
            'merchant_details' => [
                'merchant_name' => $this->config['merchant_name'],
                'merchant_url' => $this->config['merchant_url']
            ],
            'receipt' => $this->formatReceiptData($paymentDTO),
            'notification_url' => route('payment.webhook.yandexpay'),
            'return_url' => route('payment.success', ['order' => $payment->order_id]),
            'metadata' => [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id
            ]
        ];

        $response = $this->makeRequest(
            'post',
            $this->config['api_url'] . '/orders',
            $orderData,
            [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Idempotence-Key' => Str::uuid()->toString()
            ]
        );

        return [
            'payment_id' => $payment->id,
            'payment_token' => $response['payment_token'],
            'order_id' => $response['order_id']
        ];
    }

    public function processPayment(array $data): Payment
    {
        $payment = Payment::findOrFail($data['metadata']['payment_id']);

        if ($data['status'] === 'succeeded') {
            $payment->update([
                'status' => Payment::STATUS_COMPLETED,
                'provider_payment_id' => $data['payment_id'],
                'provider_data' => $data
            ]);

            // Отправляем данные в HelixMedia OFD для генерации чека
            if ($this->receiptGenerator) {
                $receipt = $this->receiptGenerator->generateReceipt($payment);
                $this->receiptGenerator->sendReceipt($receipt);
            }
        } else {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => $data['error']['description'] ?? 'Payment failed'
            ]);
        }

        return $payment;
    }

    public function checkPaymentStatus(Payment $payment): string
    {
        $response = $this->makeRequest(
            'get',
            $this->config['api_url'] . '/orders/' . $payment->provider_payment_id,
            [],
            ['Authorization' => 'Bearer ' . $this->config['api_key']]
        );

        return match ($response['status']) {
            'succeeded' => Payment::STATUS_COMPLETED,
            'canceled' => Payment::STATUS_FAILED,
            'pending' => Payment::STATUS_PENDING,
            default => Payment::STATUS_FAILED
        };
    }

    public function refundPayment(Payment $payment, ?float $amount = null): bool
    {
        $refundData = [
            'payment_id' => $payment->provider_payment_id,
            'amount' => [
                'value' => $amount ?? $payment->amount,
                'currency' => $payment->currency
            ],
            'description' => "Возврат по заказу #{$payment->order->order_number}"
        ];

        try {
            $response = $this->makeRequest(
                'post',
                $this->config['api_url'] . '/refunds',
                $refundData,
                [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Idempotence-Key' => Str::uuid()->toString()
                ]
            );

            if ($response['status'] === 'succeeded') {
                $payment->update([
                    'status' => Payment::STATUS_REFUNDED,
                    'provider_data' => array_merge(
                        $payment->provider_data ?? [],
                        ['refund' => $response]
                    )
                ]);
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('YandexPay refund error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    protected function formatReceiptData(PaymentDTO $paymentDTO): array
    {
        return [
            'customer' => [
                'email' => $paymentDTO->customer['email'],
                'phone' => $paymentDTO->customer['phone']
            ],
            'items' => array_map(function($item) {
                return [
                    'description' => $item['name'],
                    'quantity' => [
                        'value' => $item['quantity'],
                        'measure' => 'piece'
                    ],
                    'amount' => [
                        'value' => $item['price'],
                        'currency' => 'RUB'
                    ],
                    'vat_code' => $this->config['vat_code'] ?? 1,
                    'payment_subject' => 'commodity',
                    'payment_mode' => 'full_prepayment'
                ];
            }, $paymentDTO->items)
        ];
    }
} 