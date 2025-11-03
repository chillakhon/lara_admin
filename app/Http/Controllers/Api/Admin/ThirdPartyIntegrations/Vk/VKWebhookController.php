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

            Log::info("VKWebhookController: Incoming webhook", ['data' => $data]);

            $eventId = $data['event_id'] ?? null;

            // ДЕДУБЛИЗАЦИЯ: проверяем был ли этот event уже обработан
            if ($eventId && VKWebhookEvent::where('event_id', $eventId)->exists()) {
                Log::warning("VKWebhookController: Duplicate event received", ['event_id' => $eventId]);
                return response()->json(['ok' => true]); // Возвращаем ok, но не обрабатываем
            }

            // Обработка confirmation - СПЕЦИАЛЬНЫЙ СЛУЧАЙ
            if ($data['type'] === 'confirmation') {
                $settings = VKSettings::first();
                if (!$settings || !$settings->confirmation_token) {
                    Log::error("VKWebhookController: Confirmation token not found");
                    return response('', 200);
                }
                return response($settings->confirmation_token, 200);
            }

            // Валидируем подпись
            if (!$this->validateSignature($data)) {
                Log::warning("VKWebhookController: Invalid signature");
                return response()->json(['ok' => false], 403);
            }

            // Обработка обновления
            $result = $this->vkService->handleWebhookUpdate($data);

            // Сохраняем что event обработан
            if ($eventId) {
                VKWebhookEvent::create([
                    'event_id' => $eventId,
                    'type' => $data['type'],
                    'data' => $data,
                    'received_at' => now(),
                ]);
            }

            return response()->json($result ?? ['ok' => true]);

        } catch (\Exception $e) {
            Log::error("VKWebhookController: Exception in webhook", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
