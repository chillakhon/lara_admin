<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use App\Contracts\ReceiptGeneratorInterface;
use App\DTOs\PaymentDTO;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\App;
use App\Models\Setting;

class PaymentService
{
    protected array $config;
    protected ?PaymentProviderInterface $provider = null;
    protected ?ReceiptGeneratorInterface $receiptGenerator = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function provider(string $name = null): PaymentProviderInterface
    {
        if (!$name) {
            $name = $this->config['default'];
        }

        if (!isset($this->config['providers'][$name])) {
            throw new \InvalidArgumentException("Payment provider [{$name}] not found");
        }

        if (!$this->provider) {
            $config = $this->config['providers'][$name];
            $class = $config['class'];
            
            // Если нужен генератор чеков
            $receiptGenerator = null;
            if (isset($this->config['receipts'])) {
                $receiptGenerator = $this->getReceiptGenerator();
            }

            $this->provider = new $class($config, $receiptGenerator);
        }

        return $this->provider;
    }

    public function initializePayment(Order $order, string $provider = null): array
    {
        return $this->provider($provider)->initializePayment(
            PaymentDTO::fromOrder($order)
        );
    }

    public function processPayment(array $data, string $provider = null): Payment
    {
        return $this->provider($provider)->processPayment($data);
    }

    public function checkPaymentStatus(Payment $payment): string
    {
        return $this->provider($payment->provider)->checkPaymentStatus($payment);
    }

    public function refundPayment(Payment $payment, ?float $amount = null): bool
    {
        return $this->provider($payment->provider)->refundPayment($payment, $amount);
    }

    protected function getReceiptGenerator(): ?ReceiptGeneratorInterface
    {
        if ($this->receiptGenerator) {
            return $this->receiptGenerator;
        }

        $provider = $this->config['receipts']['provider'];
        $config = $this->config['receipts']['providers'][$provider];
        $class = $config['class'];

        return $this->receiptGenerator = new $class($config);
    }

    protected function getProviderConfig(string $provider): array
    {
        $settings = Setting::getGroup('payment');
        
        switch ($provider) {
            case 'yookassa':
                return [
                    'enabled' => $settings['yookassa_enabled'] ?? false,
                    'shop_id' => $settings['yookassa_shop_id'] ?? '',
                    'secret_key' => $settings['yookassa_secret_key'] ?? '',
                    'test_mode' => $settings['yookassa_test_mode'] ?? false,
                ];
                
            case 'yandexpay':
                return [
                    'enabled' => $settings['yandexpay_enabled'] ?? false,
                    'merchant_id' => $settings['yandexpay_merchant_id'] ?? '',
                    'api_key' => $settings['yandexpay_api_key'] ?? '',
                ];
                
            case 'robokassa':
                return [
                    'enabled' => $settings['robokassa_enabled'] ?? false,
                    'login' => $settings['robokassa_login'] ?? '',
                    'password1' => $settings['robokassa_password1'] ?? '',
                    'password2' => $settings['robokassa_password2'] ?? '',
                    'test_mode' => $settings['robokassa_test_mode'] ?? false,
                ];
                
            default:
                throw new \InvalidArgumentException("Unknown payment provider: {$provider}");
        }
    }
} 