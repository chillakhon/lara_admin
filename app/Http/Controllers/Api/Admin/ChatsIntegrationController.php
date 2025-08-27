<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use App\Notifications\TestMailNotification;
use App\Traits\HelperTrait;
use DefStudio\Telegraph\Models\TelegraphBot;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;


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


            // Добавьте временно в ваш контроллер
            Log::info("Webhook URL: " . env('APP_URL') . "/telegraph/" . $telegram_token . "/webhook");

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


    public function getMailSettings()
    {
        try {
            $setting = MailSetting::first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mail settings not found.',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $setting,
            ]);
        } catch (Exception $e) {
            Log::error($e);
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
            'mailer' => 'nullable|string',
            'host' => 'required|string',
            'port' => 'required|numeric',
            'username' => 'required|string',
            'password' => 'required|string',
            'encryption' => 'nullable|string',
            'from_address' => 'nullable|email',
        ]);

        $data = $request->only([
            'mailer',
            'host',
            'port',
            'username',
            'password',
            'encryption',
            'from_address',
        ]);

        // Apply defaults if missing
        $data['mailer'] = $data['mailer'] ?? 'smtp';
        $data['encryption'] = $data['encryption'] ?? 'tls';
        $data['from_address'] = $data['from_address'] ?? $data['username'];

        $setting = MailSetting::first();

        if ($setting) {
            $setting->update($data);
        } else {
            MailSetting::create($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Настройки почты успешно сохранены в базу данных.',
        ]);
    }

    public function test_mail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string'
        ]);

        Notification::route('mail', $validated['email'])->notify(new TestMailNotification());

        return response()->json([
            'success' => true,
            'message' => "Тестовое уведомление отправлено!"
        ]);
    }
}
