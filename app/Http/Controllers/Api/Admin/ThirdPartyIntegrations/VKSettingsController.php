<?php

namespace App\Http\Controllers\Api\Admin\ThirdPartyIntegrations;

use App\Http\Controllers\Controller;
use App\Models\VKSettings;
use Illuminate\Http\Request;

class VKSettingsController extends Controller
{
    // Получение настроек (если есть)
    public function getVKSettings()
    {
        $settings = VKSettings::first();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    // Создание или обновление настроек
    public function configuration(Request $request)
    {
        $validated = $request->validate([
            'community_id' => 'required|string',
            'access_token' => 'required|string',
            'confirmation_token' => 'nullable|string',
            'api_version' => 'nullable|string',
        ]);

        // Ищем существующую запись
        $settings = VKSettings::first();

        if ($settings) {
            // Обновляем
            $settings->update($validated);
        } else {
            // Создаём новую
            $settings = VKSettings::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Настройки ВК сохранены',
            'data' => $settings,
        ]);
    }

}
