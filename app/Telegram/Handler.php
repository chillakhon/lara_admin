<?php

namespace App\Telegram;

use App\Models\Client;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{

    private string $userName = '';


    public function handle(Request $request, TelegraphBot $bot): void
    {
        $update = $request->all();

        if (isset($update['pre_checkout_query'])) {
            $this->handlePreCheckoutQuery($update['pre_checkout_query']);
        }
        Log::info('Fired handle method ' );
    }

    public function start(): void
    {
        $this->userName = substr($this->chat->name, 10);
        $this->chat->markdownV2('Добрый день, ' . $this->userName)->send();

    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        $this->reply('Неизвестная команда');
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $this->reply('не понял');
    }

    public function handlePreCheckoutQuery($preCheckoutQuery): JsonResponse
    {
        $queryId = $preCheckoutQuery['id'];

        // Всегда отвечаем положительно, без дополнительных проверок
        $this->answerPreCheckoutQuery($queryId, true);

        return response()->json(['ok' => true]);
    }

    private function answerPreCheckoutQuery($queryId, $ok): void
    {
        $botToken = config('services.telegram.bot_token');

        $response = Http::post("https://api.telegram.org/bot{$botToken}/answerPreCheckoutQuery", [
            'pre_checkout_query_id' => $queryId,
            'ok' => $ok,
        ]);

        if (!$response->successful()) {
            Log::error('Failed to answer pre-checkout query', ['response' => $response->json()]);
        }
    }


}
