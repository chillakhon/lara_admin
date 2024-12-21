<?php

namespace App\Services\Receipt;

use App\Contracts\ReceiptGeneratorInterface;
use App\DTOs\ReceiptDTO;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HelixmediaReceiptService implements ReceiptGeneratorInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generateReceipt(Payment $payment): ReceiptDTO
    {
        return ReceiptDTO::fromPayment($payment);
    }

    public function sendReceipt(ReceiptDTO $receipt): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json'
            ])->post($this->config['api_url'] . '/receipts', [
                'external_id' => $receipt->paymentId,
                'receipt_type' => 'sale',
                'timestamp' => now()->format('Y-m-d\TH:i:s'),
                'buyer' => [
                    'email' => $receipt->customer['email'],
                    'phone' => $receipt->customer['phone']
                ],
                'items' => array_map(function($item) {
                    return [
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'sum' => $item['sum'],
                        'payment_method' => 'full_prepayment',
                        'payment_object' => 'commodity',
                        'vat' => [
                            'type' => $this->config['vat_type'] ?? 'vat20'
                        ]
                    ];
                }, $receipt->items),
                'total' => $receipt->total,
                'payments' => [
                    [
                        'type' => $this->mapPaymentMethod($receipt->paymentMethod),
                        'sum' => $receipt->total
                    ]
                ]
            ]);

            if ($response->successful()) {
                $receiptData = $response->json();
                
                // Сохраняем информацию о чеке
                PaymentReceipt::create([
                    'payment_id' => $receipt->paymentId,
                    'receipt_number' => $receiptData['receipt_number'],
                    'provider' => 'helixmedia',
                    'status' => PaymentReceipt::STATUS_SENT,
                    'receipt_data' => $receiptData
                ]);

                return true;
            }

            Log::error('Helixmedia receipt error', [
                'payment_id' => $receipt->paymentId,
                'response' => $response->json()
            ]);

            // Сохраняем информацию об ошибке
            PaymentReceipt::create([
                'payment_id' => $receipt->paymentId,
                'provider' => 'helixmedia',
                'status' => PaymentReceipt::STATUS_FAILED,
                'error_message' => $response->body(),
                'receipt_data' => $response->json()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Helixmedia receipt error', [
                'payment_id' => $receipt->paymentId,
                'error' => $e->getMessage()
            ]);

            PaymentReceipt::create([
                'payment_id' => $receipt->paymentId,
                'provider' => 'helixmedia',
                'status' => PaymentReceipt::STATUS_FAILED,
                'error_message' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function checkReceiptStatus(string $receiptNumber): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key']
            ])->get($this->config['api_url'] . '/receipts/' . $receiptNumber);

            if ($response->successful()) {
                $data = $response->json();
                return $data['status'];
            }
        } catch (\Exception $e) {
            Log::error('Helixmedia check receipt status error', [
                'receipt_number' => $receiptNumber,
                'error' => $e->getMessage()
            ]);
        }

        return PaymentReceipt::STATUS_FAILED;
    }

    protected function mapPaymentMethod(string $method): string
    {
        return match ($method) {
            'yookassa' => 'electronic',
            'yandexpay' => 'electronic',
            'cloudpayment' => 'electronic',
            'robokassa' => 'electronic',
            default => 'cash'
        };
    }
} 