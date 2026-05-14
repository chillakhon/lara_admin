<?php

namespace App\Services\Payment;

use App\DTOs\PaymentDTO;
use App\Models\Payment;

class CloudPaymentProvider extends AbstractPaymentProvider
{
    public static function getProviderCode(): string
    {
        return 'cloudpayment';
    }

    public function initializePayment(PaymentDTO $paymentDTO): array
    {
        $payment = $this->createPayment($paymentDTO);

        return [
            'payment_id' => $payment->id,
            'public_id' => $this->config['public_id'],
            'amount' => $paymentDTO->amount,
            'currency' => $paymentDTO->currency,
            'description' => $paymentDTO->description,
            'invoice_id' => $payment->id,
            'account_id' => $paymentDTO->customer['email'],
            'data' => [
                'cloudPayments' => [
                    'recurrent' => [
                        'interval' => 'Month',
                        'period' => 1
                    ]
                ]
            ]
        ];
    }

    public function processPayment(array $data): Payment
    {
        $payment = Payment::findOrFail($data['InvoiceId']);

        if ($data['Success']) {
            $payment->update([
                'status' => Payment::STATUS_COMPLETED,
                'provider_payment_id' => $data['TransactionId'],
                'provider_data' => $data
            ]);

            if ($this->receiptGenerator) {
                $receipt = $this->receiptGenerator->generateReceipt($payment);
                $this->receiptGenerator->sendReceipt($receipt);
            }
        } else {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => $data['Message'] ?? 'Payment failed'
            ]);
        }

        return $payment;
    }

    public function checkPaymentStatus(Payment $payment): string
    {
        $response = $this->makeRequest(
            'post',
            $this->config['api_url'] . '/payments/find',
            ['InvoiceId' => $payment->id],
            [
                'Authorization' => 'Basic ' . base64_encode(
                    $this->config['public_id'] . ':' . $this->config['api_secret']
                )
            ]
        );

        return $response['Success'] ? Payment::STATUS_COMPLETED : Payment::STATUS_FAILED;
    }

    public function refundPayment(Payment $payment, ?float $amount = null): bool
    {
        $response = $this->makeRequest(
            'post',
            $this->config['api_url'] . '/payments/refund',
            [
                'TransactionId' => $payment->provider_payment_id,
                'Amount' => $amount ?? $payment->amount
            ],
            [
                'Authorization' => 'Basic ' . base64_encode(
                    $this->config['public_id'] . ':' . $this->config['api_secret']
                )
            ]
        );

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
} 