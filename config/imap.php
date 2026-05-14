<?php

return [
    'default' => 'default',

    'accounts' => [
        'default' => [
            'host' => env('IMAP_HOST', 'imap.yandex.ru'),
            'port' => env('IMAP_PORT', 993),
            'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
            'username' => env('IMAP_USERNAME'),
            'password' => env('IMAP_PASSWORD'),
            'validate_cert' => true,
            'protocol' => 'imap'
        ]
    ]
];
