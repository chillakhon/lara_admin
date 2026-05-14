<?php

namespace App\Services\Payment;

use App\DTOs\PaymentDTO;
use App\Models\Payment;
use YooKassa\Client;
use YooKassa\Model\Payment as YooKassaPayment;
use YooKassa\Model\PaymentStatus;
use YooKassa\Request\Payments\CreatePaymentRequest;
use YooKassa\Request\Payments\Payment\CreateCaptureRequest;

class YookassaProvider extends AbstractPaymentProvider
{
    protected Client $client;

    public function __construct(array $config, ?\App\Contracts\ReceiptGeneratorInterface $receiptGenerator = null)
    {
        parent::__construct($config, $receiptGenerator);
        
        $this->client = new Client();
        $this->client->setAuth(
            $this->config['shop_id'],
            $this->config['secret_key']
        );
    }

    public static function getProviderCode(): string
    {
        return 'yookassa';
    }

    public function initializePayment(PaymentDTO $paymentDTO): array
    {
        $payment = $this->createPayment($paymentDTO);

        $builder = CreatePaymentRequest::builder();
        $builder->setAmount([
                'value' => $paymentDTO->amount,
                'currency' => $paymentDTO->currency
            ])
            ->setCapture(true)
            ->setDescription($paymentDTO->description)
            ->setConfirmation([
                'type' => 'redirect',
                'return_url' => route('payment.success', ['order' => $payment->order_id])
            ])
            ->setMetadata([
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id
            ]);

        // Добавляем данные для чека
        if ($this->config['send_receipt']) {
            $builder->setReceiptEmail($paymentDTO->customer['email'])
                ->setReceiptPhone($paymentDTO->customer['phone'])
                ->setReceipt([
                    'customer' => [
                        'email' => $paymentDTO->customer['email'],
                        'phone' => $paymentDTO->customer['phone']
                    ],
                    'items' => $this->formatReceiptItems($paymentDTO->items)
                ]);
        }

        $response = $this->client->createPayment(
            $builder->build(),
            uniqid('', true)
        );

        return [
            'payment_id' => $payment->id,
            'confirmation_url' => $response->getConfirmation()->getConfirmationUrl(),
            'provider_payment_id' => $response->getId()
        ];
    }

    public function processPayment(array $data): Payment
    {
        $payment = Payment::findOrFail($data['object']['metadata']['payment_id']);
        $status = $data['object']['status'];

        if ($status === PaymentStatus::SUCCEEDED) {
            $payment->update([
                'status' => Payment::STATUS_COMPLETED,
                'provider_payment_id' => $data['object']['id'],
                'provider_data' => $data['object']
            ]);

            // Если чеки не через Юкассу, а через HelixMedia
            if ($this->receiptGenerator && !$this->config['send_receipt']) {
                $receipt = $this->receiptGenerator->generateReceipt($payment);
                $this->receiptGenerator->sendReceipt($receipt);
            }
        } else {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => $data['object']['cancellation_details']['reason'] ?? 'Payment failed'
            ]);
        }

        return $payment;
    }

    public function checkPaymentStatus(Payment $payment): string
    {
        $response = $this->client->getPaymentInfo($payment->provider_payment_id);

        return match ($response->getStatus()) {
            PaymentStatus::SUCCEEDED => Payment::STATUS_COMPLETED,
            PaymentStatus::CANCELED => Payment::STATUS_FAILED,
            PaymentStatus::PENDING => Payment::STATUS_PENDING,
            default => Payment::STATUS_FAILED
        };
    }

    public function refundPayment(Payment $payment, ?float $amount = null): bool
    {
        try {
            $response = $this->client->createRefund(
                [
                    'payment_id' => $payment->provider_payment_id,
                    'amount' => [
                        'value' => $amount ?? $payment->amount,
                        'currency' => $payment->currency
                    ],
                    'description' => "Возврат по заказу #{$payment->order->order_number}"
                ],
                uniqid('', true)
            );

            if ($response->getStatus() === 'succeeded') {
                $payment->update([
                    'status' => Payment::STATUS_REFUNDED,
                    'provider_data' => array_merge(
                        $payment->provider_data ?? [],
                        ['refund' => $response->toArray()]
                    )
                ]);
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Yookassa refund error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    protected function formatReceiptItems(array $items): array
    {
        return array_map(function($item) {
            return [
                'description' => $item['name'],
                'quantity' => $item['quantity'],
                'amount' => [
                    'value' => $item['price'],
                    'currency' => 'RUB'
                ],
                'vat_code' => $this->config['vat_code'] ?? '1',
                'payment_subject' => 'commodity',
                'payment_mode' => 'full_prepayment'
            ];
        }, $items);
    }
} 