<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsappService
{
    protected $phoneNumberId;
    protected $token;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->token = config('services.whatsapp.token');
    }

    public function sendTextMessage($to, $message)
    {
        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        return Http::withToken($this->token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => 'hello_world',
                'language' => [
                    'code' => 'en_US',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $message,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function order_notification($to, $orderNumber, $date, $amount)
    {
        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        return Http::withToken($this->token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => 'auto_pay_reminder_2',
                'language' => [
                    'code' => 'en_US',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $orderNumber],
                            ['type' => 'text', 'text' => $date],
                            ['type' => 'text', 'text' => $amount],
                        ],
                    ]
                ]
            ],
        ]);
    }

    
}