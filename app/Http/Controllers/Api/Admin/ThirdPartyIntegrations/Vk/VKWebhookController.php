<?php

namespace App\Http\Controllers\Api\Admin\ThirdPartyIntegrations\Vk;

use App\Http\Controllers\Controller;
use App\Models\VKWebhookEvent;
use App\Services\Vk\VKService;
use App\Models\VKSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VKWebhookController extends Controller
{
    protected VKService $vkService;

    public function __construct(VKService $vkService)
    {
        $this->vkService = $vkService;
    }

    /**
     * Webhook endpoint для ВК
     * POST /api/vk/webhook
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->json()->all();
            $eventId = $data['event_id'] ?? null;

            // Проверяем дубликат ПЕРЕД обработкой
            if ($eventId && VKWebhookEvent::where('event_id', $eventId)->exists()) {
                Log::warning("VKWebhookController: Duplicate event skipped", ['event_id' => $eventId]);
                return response()->json(['ok' => true]);
            }

            Log::warning("VKWebhookController: ", ['data' => $data]);

            // Confirmation
            if ($data['type'] === 'confirmation') {
                $settings = VKSettings::first();
                if (!$settings || !$settings->confirmation_token) {
                    return response('', 200);
                }
                return response($settings->confirmation_token, 200);
            }

            // Обработка
            $result = $this->vkService->handleWebhookUpdate($data);

            // Сохраняем event ТОЛЬКО если обработка прошла успешно
            if ($eventId && ($result['ok'] ?? false)) {
                try {
                    VKWebhookEvent::create([
                        'event_id' => $eventId,
                        'type' => $data['type'],
                        'data' => $data,
                        'received_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Could not save webhook event", ['error' => $e->getMessage()]);
                }
            }

            return response()->json($result ?? ['ok' => true]);

        } catch (\Exception $e) {
            Log::error("VKWebhookController: Exception", ['error' => $e->getMessage()]);
            return response()->json(['ok' => true], 200);
        }
    }


    /**
     * Валидировать подпись ВК webhook'а
     */
    protected function validateSignature(array $data): bool
    {
        $settings = VKSettings::first();

        if (!$settings || !isset($data['secret'])) {
            return true; // Пока пропускаем валидацию
        }

        // TODO: Реализовать полную валидацию подписи если нужна
        // https://vk.com/dev/callback_api

        return true;
    }
}
