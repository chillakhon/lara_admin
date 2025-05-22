<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HelperTrait;
use Artisan;
use DefStudio\Telegraph\Models\TelegraphBot;
use Exception;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Log;

class ChatsIntegrationController extends Controller
{
    use HelperTrait;
    public function telegram_integration(Request $request)
    {
        try {

            $request->validate([
                'token' => 'required|string',
                'bot_name' => 'required|string',
            ]);

            $telegram_token = $this->decryptToken($request->get('token'));

            $response = Http::get("https://api.telegram.org/bot{$telegram_token}/setWebhook", [
                'url' => env('APP_URL') . "/telegraph/" . $telegram_token . "/webhook"
            ]);

            if (!$response->ok()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Telegram API error',
                    'telegram_response' => $response->json(),
                ]);
            }

            $bot = TelegraphBot
                ::where('token', $telegram_token)
                ->first();

            if (!$bot) {
                $bot = TelegraphBot::create([
                    'token' => $telegram_token,
                    'name' => $request->get('bot_name'),
                ]);
            }

            $bot->registerCommands([
                "help" => "Что умеет этот бот",
                "start" => "Начать использовать наш бот",
                "orders" => "Ожидающие заказы",
                "reset" => "Сбросить данные и начать заново"
            ])->send();

            return response()->json([
                'success' => true,
                'message' => "Bot was connected!"
            ]);
        } catch (Exception $e) {
            Log::info($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
        }
    }


    public function updateMailSettings(Request $request)
    {

        $request->validate([
            'MAIL_MAILER' => 'required|string',
            'MAIL_HOST' => 'required|string',
            'MAIL_PORT' => 'required|numeric',
            'MAIL_USERNAME' => 'required|string',
            'MAIL_PASSWORD' => 'required|string',
            'MAIL_ENCRYPTION' => 'required|string',
            'MAIL_FROM_ADDRESS' => 'required|email',
        ]);

        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return response()->json(['error' => '.env file not found'], 404);
        }

        $data = $request->only([
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS',
        ]);

        foreach ($data as $key => $value) {
            self::setEnvValue($key, $value);
        }

        return response()->json(['success' => true, 'message' => 'Mail settings updated successfully']);
    }

    private static function setEnvValue($key, $value)
    {
        $envPath = base_path('.env');
        $escaped = preg_quote('=' . env($key), '/');

        if (env($key) !== null) {
            File::put($envPath, preg_replace(
                "/^$key$escaped/m",
                "$key=\"$value\"",
                File::get($envPath)
            ));
        } else {
            File::append($envPath, "\n$key=\"$value\"");
        }
    }
}
