<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use App\Contracts\ReceiptGeneratorInterface;
use App\DTOs\PaymentDTO;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractPaymentProvider implements PaymentProviderInterface
{
    protected ReceiptGeneratorInterface $receiptGenerator;
    protected array $config;

    public function __construct(array $config, ?ReceiptGeneratorInterface $receiptGenerator = null)
    {
        $this->config = $config;
        if ($receiptGenerator) {
            $this->receiptGenerator = $receiptGenerator;
        }
    }

    protected function createPayment(PaymentDTO $paymentDTO, string $status = Payment::STATUS_PENDING): Payment
    {
        return Payment::create([
            'order_id' => $paymentDTO->orderId,
            'provider' => static::getProviderCode(),
            'amount' => $paymentDTO->amount,
            'currency' => $paymentDTO->currency,
            'status' => $status
        ]);
    }

    protected function makeRequest(string $method, string $url, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Payment provider error', [
                'provider' => static::getProviderCode(),
                'url' => $url,
                'data' => $data,
                'response' => $response->json()
            ]);

            throw new \Exception('Payment provider error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Payment request error', [
                'provider' => static::getProviderCode(),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    abstract public static function getProviderCode(): string;
} 