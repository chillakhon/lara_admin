<?php

namespace App\Http\Controllers\Api\Admin\ThirdPartyIntegrations\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsSettingsController extends Controller
{
    /**
     * Получить настройки аналитики (Яндекс.Метрика)
     *
     * @return JsonResponse
     */
    public function getAnalyticsSettings(): JsonResponse
    {
        $settings = Setting::getGroup('analytics');

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Сохранить или обновить настройки Яндекс.Метрики
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateYandexMetrika(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'yandex_metrika_enabled' => 'required|boolean',
            'yandex_metrika_id' => 'required_if:yandex_metrika_enabled,true|nullable|string',
            'yandex_metrika_webvisor' => 'boolean',
            'yandex_metrika_clickmap' => 'boolean',
            'yandex_metrika_tracklinks' => 'boolean',
            'yandex_metrika_accurateTrackBounce' => 'boolean',
        ]);

        // Сохраняем каждую настройку в таблицу settings
        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'analytics');
        }

        return response()->json([
            'success' => true,
            'message' => 'Настройки Яндекс.Метрики успешно сохранены',
            'data' => Setting::getGroup('analytics'),
        ]);
    }
}
