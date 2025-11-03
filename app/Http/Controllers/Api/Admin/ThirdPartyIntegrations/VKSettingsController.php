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

    // Проверка подключения к ВК
    public function test()
    {
        $settings = VKSettings::first();

        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'Настройки ВК не найдены',
            ], 404);
        }

        try {
            // Тестовый запрос к ВК API
            $response = \Http::get('https://api.vk.com/method/groups.getById', [
                'group_id' => $settings->community_id,
                'access_token' => $settings->access_token,
                'v' => $settings->api_version,
            ]);

            if ($response->successful() && !isset($response['error'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Подключение к ВК успешно!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ошибка ВК: ' . ($response['error']['error_msg'] ?? 'Unknown error'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка подключения: ' . $e->getMessage(),
            ], 500);
        }
    }
}
