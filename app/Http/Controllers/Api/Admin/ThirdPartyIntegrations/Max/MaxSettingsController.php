<?php

namespace App\Http\Controllers\Api\Admin\ThirdPartyIntegrations\Max;

use App\Http\Controllers\Controller;
use App\Models\MaxSettings;
use App\Services\Max\MaxService;
use Illuminate\Http\Request;

class MaxSettingsController extends Controller
{
    protected MaxService $maxService;

    public function __construct(MaxService $maxService)
    {
        $this->maxService = $maxService;
    }

    /**
     * Получить настройки Max
     * GET /admin/max/settings
     */
    public function index()
    {
        $settings = MaxSettings::first();

        return response()->json($settings);
    }

    /**
     * Сохранить настройки и автоматически зарегистрировать webhook
     * POST /admin/max/settings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bot_token' => 'required|string',
            'is_active' => 'boolean',
        ]);

        // Сохраняем настройки
        $settings = MaxSettings::first();

        $tokenChanged = $settings && $settings->bot_token !== $validated['bot_token'];

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = MaxSettings::create($validated);
        }

        // Если токен изменился — перерегистрируем webhook (старый удаляем, новый создаём)
        if ($tokenChanged) {
            $url = rtrim(config('services.max.webhook_url'), '/') . '/api/public/max/webhook';
            $this->maxService->unregisterWebhook($url);
        }

        // Автоматически регистрируем webhook
        $webhookResult = $this->maxService->registerWebhookIfNeeded();

        return response()->json([
            'settings' => $settings,
            'webhook' => $webhookResult,
        ]);
    }

    /**
     * Тест подключения к Max API
     * POST /admin/max/settings/test
     */
    public function testConnection()
    {
        try {
            $client = $this->maxService->getApiClient();
            $botInfo = $client->getBotInfo();

            return response()->json([
                'success' => true,
                'bot_info' => $botInfo->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Получить текущий webhook URL (с /api/max/webhook)
     * GET /admin/max/webhook/url
     */
    public function getWebhookUrl()
    {
        $baseUrl = config('services.max.webhook_url');
        $fullUrl = rtrim($baseUrl, '/').'/api/max/webhook';
        $secret = config('services.max.webhook_secret');

        return response()->json([
            'base_url' => $baseUrl,
            'full_url' => $fullUrl,
            'has_secret' => ! empty($secret),
        ]);
    }

    /**
     * Получить список webhook подписок
     * GET /admin/max/webhook/subscriptions
     */
    public function getSubscriptions()
    {
        $subscriptions = $this->maxService->getWebhookSubscriptions();
        $currentUrl = rtrim(config('services.max.webhook_url'), '/').'/api/max/webhook';

        return response()->json([
            'subscriptions' => $subscriptions,
            'current_url' => $currentUrl,
        ]);
    }

    /**
     * Удалить webhook подписку
     * POST /admin/max/webhook/unregister
     */
    public function unregisterWebhook()
    {
        $url = rtrim(config('services.max.webhook_url'), '/').'/api/max/webhook';
        $result = $this->maxService->unregisterWebhook($url);

        return response()->json($result);
    }

    /**
     * Принудительно перерегистрировать webhook
     * POST /admin/max/webhook/reregister
     */
    public function reregisterWebhook()
    {
        // Сначала удаляем
        $url = rtrim(config('services.max.webhook_url'), '/').'/api/max/webhook';
        $this->maxService->unregisterWebhook($url);

        // Затем регистрируем заново
        $result = $this->maxService->registerWebhookIfNeeded();

        return response()->json($result);
    }
}
