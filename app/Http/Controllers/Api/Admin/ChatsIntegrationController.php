<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Artisan;
use DefStudio\Telegraph\Models\TelegraphBot;
use Exception;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ChatsIntegrationController extends Controller
{
    public function telegram_integration(Request $request)
    {
        try {

            $request->validate([
                'token' => 'required|string',
                'bot_name' => 'required|string',
            ]);

            $bot = TelegraphBot
                ::where('token', $request->get('token'))
                ->first();

            if (!$bot) {
                $bot = TelegraphBot::create([
                    'token' => $request->get('token'),
                    'name' => $request->get('bot_name'),
                ]);
            }

            $response = Http::get("https://api.telegram.org/bot{$bot->token}/setWebhook", [
                'url' => env('APP_URL'),
            ]);

            if (!$response->ok()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Telegram API error',
                    'telegram_response' => $response->json(),
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
