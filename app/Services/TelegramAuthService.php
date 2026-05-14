<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class TelegramAuthService
{
    public static function checkHash($initData): bool
    {
        $token = Config::get('telegram.bot_token');

        $needle = 'hash=';
        $received_hash = '';

        $initDataArr = explode('&', rawurldecode($initData));

        foreach ($initDataArr as &$data) {
            if (substr($data, 0, strlen($needle)) === $needle) {
                $received_hash = substr_replace($data, '', 0, strlen($needle));
                $data = null;
            }
        }

        $initDataArray = array_filter($initDataArr);
        sort($initDataArray);
        $data_check_string = implode("\n", $initDataArray);

        $secret_key = hash_hmac('sha256', $token, 'WebAppData', true);
        $hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));

        if (strcmp($hash, $received_hash) !== 0) {
            return false;
        }
        return true;

    }
}
