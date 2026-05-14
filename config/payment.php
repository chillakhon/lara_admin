<?php

return [
    // Провайдер по умолчанию
    'default' => env('DEFAULT_PAYMENT_PROVIDER', 'yookassa'),

    // Настройки провайдеров
    'providers' => [
        'yookassa' => [
            'class' => \App\Services\Payment\YookassaProvider::class,
            'shop_id' => env('YOOKASSA_SHOP_ID'),
            'secret_key' => env('YOOKASSA_SECRET_KEY'),
            'send_receipt' => env('YOOKASSA_SEND_RECEIPT', false),
            'vat_code' => env('YOOKASSA_VAT_CODE', '1'),
        ],

        'yandexpay' => [
            'class' => \App\Services\Payment\YandexPayProvider::class,
            'merchant_id' => env('YANDEXPAY_MERCHANT_ID'),
            'api_key' => env('YANDEXPAY_API_KEY'),
            'merchant_name' => env('YANDEXPAY_MERCHANT_NAME'),
            'merchant_url' => env('APP_URL'),
            'api_url' => env('YANDEXPAY_API_URL', 'https://pay.yandex.ru/api/v3'),
            'vat_code' => env('YANDEXPAY_VAT_CODE', 1),
        ],

        'cloudpayment' => [
            'class' => \App\Services\Payment\CloudPaymentProvider::class,
            'public_id' => env('CLOUDPAYMENT_PUBLIC_ID'),
            'api_secret' => env('CLOUDPAYMENT_API_SECRET'),
            'api_url' => env('CLOUDPAYMENT_API_URL', 'https://api.cloudpayments.ru'),
        ],

        'robokassa' => [
            'class' => \App\Services\Payment\RobokassaProvider::class,
            'merchant_login' => env('ROBOKASSA_LOGIN'),
            'password1' => env('ROBOKASSA_PASSWORD1'),
            'password2' => env('ROBOKASSA_PASSWORD2'),
            'is_test' => env('ROBOKASSA_TEST_MODE', false),
            'payment_url' => env('ROBOKASSA_PAYMENT_URL', 'https://auth.robokassa.ru/Merchant/Index'),
            'status_url' => env('ROBOKASSA_STATUS_URL', 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx/OpState'),
            'refund_url' => env('ROBOKASSA_REFUND_URL', 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx/RefundPayment'),
        ],
    ],

    // Настройки чеков
    'receipts' => [
        'provider' => env('RECEIPT_PROVIDER', 'helixmedia'),

        'providers' => [
            'helixmedia' => [
                'class' => \App\Services\Receipt\HelixmediaReceiptService::class,
                'api_key' => env('HELIXMEDIA_API_KEY'),
                'api_url' => env('HELIXMEDIA_API_URL'),
                'vat_type' => env('HELIXMEDIA_VAT_TYPE', 'vat20'),
            ],
        ],
    ],
]; 