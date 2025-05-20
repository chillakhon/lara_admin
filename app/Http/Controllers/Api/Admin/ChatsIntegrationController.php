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
}
