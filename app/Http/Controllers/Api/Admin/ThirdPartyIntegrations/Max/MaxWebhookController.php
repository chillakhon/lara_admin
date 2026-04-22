<?php

namespace App\Http\Controllers\Api\Admin\ThirdPartyIntegrations\Max;

use App\Http\Controllers\Controller;
use App\Services\Max\MaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaxWebhookController extends Controller
{
    protected MaxService $maxService;

    public function __construct(MaxService $maxService)
    {
        $this->maxService = $maxService;
    }

    /**
     * Webhook endpoint для Max
     * POST /api/max/webhook
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->json()->all();

            Log::info('Max webhook received', ['data' => $data]);

            // Обработка через сервис
            $result = $this->maxService->handleWebhookUpdate($data);

            return response()->json($result ?? ['ok' => true]);

        } catch (\Exception $e) {
            Log::error('MaxWebhookController: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['ok' => true], 200);
        }
    }
}
