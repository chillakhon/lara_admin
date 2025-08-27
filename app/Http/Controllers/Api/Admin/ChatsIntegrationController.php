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
            Log::info("=== TELEGRAM INTEGRATION START ===");

            $request->validate([
                'token' => 'required|string',
                'bot_name' => 'required|string',
            ]);

            $telegram_token = $this->decryptToken($request->get('token'));
            $webhook_url = env('APP_URL') . "/telegraph/" . $telegram_token . "/webhook";

            Log::info("APP_URL: " . env('APP_URL'));
            Log::info("Generated webhook URL: " . $webhook_url);
            Log::info("Token (first 10 chars): " . substr($telegram_token, 0, 10) . "...");

            // 1. Проверяем токен бота
            Log::info("--- Checking bot token ---");
            $bot_info = Http::get("https://api.telegram.org/bot{$telegram_token}/getMe");
            Log::info("Bot info response status: " . $bot_info->status());
            Log::info("Bot info response: " . $bot_info->body());

            if (!$bot_info->ok()) {
                Log::error("Invalid bot token!");
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid bot token',
                    'bot_response' => $bot_info->json(),
                ]);
            }

            // 2. Проверяем текущий webhook
            Log::info("--- Checking current webhook ---");
            $current_webhook = Http::get("https://api.telegram.org/bot{$telegram_token}/getWebhookInfo");
            Log::info("Current webhook status: " . $current_webhook->status());
            Log::info("Current webhook info: " . $current_webhook->body());

            // 3. Проверяем доступность нашего URL
            Log::info("--- Testing our webhook URL ---");
            try {
                $test_response = Http::timeout(10)->get($webhook_url);
                Log::info("Test GET status: " . $test_response->status());
                Log::info("Test GET response: " . $test_response->body());
            } catch (Exception $test_e) {
                Log::error("Test GET failed: " . $test_e->getMessage());
            }

            // 4. Устанавливаем webhook (используем POST)
            Log::info("--- Setting webhook ---");
            $response = Http::post("https://api.telegram.org/bot{$telegram_token}/setWebhook", [
                'url' => $webhook_url,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true
            ]);

            Log::info("SetWebhook response status: " . $response->status());
            Log::info("SetWebhook response: " . $response->body());

            if (!$response->ok()) {
                Log::error("SetWebhook failed!");
                return response()->json([
                    'success' => false,
                    'message' => 'Telegram API error',
                    'telegram_response' => $response->json(),
                    'webhook_url' => $webhook_url,
                ]);
            }

            // 5. Создаем/находим бота в БД
            Log::info("--- Creating/finding bot in database ---");
            $bot = TelegraphBot::where('token', $telegram_token)->first();

            if (!$bot) {
                $bot = TelegraphBot::create([
                    'token' => $telegram_token,
                    'name' => $request->get('bot_name'),
                ]);
                Log::info("Created new bot with ID: " . $bot->id);
            } else {
                Log::info("Found existing bot with ID: " . $bot->id);
            }

            // 6. Регистрируем команды
            Log::info("--- Registering commands ---");
            try {
                $bot->registerCommands([
                    "help" => "Что умеет этот бот",
                    "start" => "Начать использовать наш бот",
                    "orders" => "Ожидающие заказы",
                    "reset" => "Сбросить данные и начать заново"
                ])->send();
                Log::info("Commands registered successfully");
            } catch (Exception $cmd_e) {
                Log::error("Commands registration failed: " . $cmd_e->getMessage());
            }

            Log::info("=== TELEGRAM INTEGRATION SUCCESS ===");

            return response()->json([
                'success' => true,
                'message' => "Bot was connected!",
                'webhook_url' => $webhook_url
            ]);

        } catch (Exception $e) {
            Log::error("=== TELEGRAM INTEGRATION ERROR ===");
            Log::error("Exception: " . $e->getMessage());
            Log::error("File: " . $e->getFile());
            Log::error("Line: " . $e->getLine());
            Log::error("Trace: " . $e->getTraceAsString());

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
