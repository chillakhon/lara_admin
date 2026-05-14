<?php

namespace App\Http\Controllers\Api\Admin\ThirdPartyIntegrations\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Endpoint для webhook'а от Telegram
     * URL: /api/telegram/webhook
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->json()->all();

            Log::info('TelegramWebhookController: Incoming webhook', ['data' => $data]);

            // Обработка webhook'а
            $result = $this->telegramService->handleWebhookUpdate($data);

            // Всегда возвращаем 200 OK чтобы Telegram не переотправлял
            return response()->json($result ?? ['ok' => true], 200);

        } catch (\Exception $e) {
            Log::error('TelegramWebhookController: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['ok' => true], 200);
        }
    }
}
