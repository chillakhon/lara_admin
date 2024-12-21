<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function general()
    {
        return Inertia::render('Dashboard/Settings/General', [
            'settings' => Setting::getGroup('general')
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string',
            'company_address' => 'required|string',
            'company_phone' => 'required|string',
            'company_email' => 'required|email',
            'default_currency' => 'required|string|size:3',
            'timezone' => 'required|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'general');
        }

        return redirect()->back()->with('success', 'Настройки успешно обновлены');
    }

    public function integrations()
    {
        return Inertia::render('Dashboard/Settings/Integrations', [
            'settings' => Setting::getGroup('integrations')
        ]);
    }

    public function updateIntegrations(Request $request)
    {
        $validated = $request->validate([
            'cdek_account' => 'required|string',
            'cdek_password' => 'required|string',
            'cdek_test_mode' => 'boolean',
            // Другие интеграции
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'integrations');
        }

        return redirect()->back()->with('success', 'Настройки интеграций обновлены');
    }

    public function apiKeys()
    {
        return Inertia::render('Dashboard/Settings/ApiKeys', [
            'apiKeys' => ApiKey::where('user_id', auth()->id())->get()
        ]);
    }

    public function updateApiKeys(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array'
        ]);

        $apiKey = ApiKey::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'key' => Str::random(32),
            'permissions' => $validated['permissions']
        ]);

        return redirect()->back()->with('success', 'API ключ создан');
    }

    public function notifications()
    {
        return Inertia::render('Dashboard/Settings/Notifications', [
            'settings' => Setting::getGroup('notifications')
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => 'required|array',
            'telegram_notifications' => 'required|array',
            'telegram_bot_token' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'notifications');
        }

        return redirect()->back()->with('success', 'Настройки уведомлений обновлены');
    }

    public function payment()
    {
        return Inertia::render('Dashboard/Settings/Payment', [
            'settings' => Setting::getGroup('payment')
        ]);
    }

    public function updatePayment(Request $request)
    {
        $validated = $request->validate([
            'yookassa_enabled' => 'boolean',
            'yookassa_shop_id' => 'required_if:yookassa_enabled,true',
            'yookassa_secret_key' => 'required_if:yookassa_enabled,true',
            'yookassa_test_mode' => 'boolean',

            'yandexpay_enabled' => 'boolean',
            'yandexpay_merchant_id' => 'required_if:yandexpay_enabled,true',
            'yandexpay_api_key' => 'required_if:yandexpay_enabled,true',

            'robokassa_enabled' => 'boolean',
            'robokassa_login' => 'required_if:robokassa_enabled,true',
            'robokassa_password1' => 'required_if:robokassa_enabled,true',
            'robokassa_password2' => 'required_if:robokassa_enabled,true',
            'robokassa_test_mode' => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'payment');
        }

        return redirect()->back()->with('success', 'Настройки платежных систем обновлены');
    }

    public function delivery()
    {
        return Inertia::render('Dashboard/Settings/Delivery', [
            'settings' => Setting::getGroup('delivery')
        ]);
    }

    public function updateDelivery(Request $request)
    {
        $validated = $request->validate([
            'cdek_enabled' => 'boolean',
            'cdek_account' => 'required_if:cdek_enabled,true',
            'cdek_password' => 'required_if:cdek_enabled,true',
            'cdek_test_mode' => 'boolean',
            'cdek_sender_city_id' => 'required_if:cdek_enabled,true',

            'russian_post_enabled' => 'boolean',
            'russian_post_token' => 'required_if:russian_post_enabled,true',
            'russian_post_login' => 'required_if:russian_post_enabled,true',
            'russian_post_password' => 'required_if:russian_post_enabled,true',
            'russian_post_test_mode' => 'boolean',

            'boxberry_enabled' => 'boolean',
            'boxberry_token' => 'required_if:boxberry_enabled,true',
            'boxberry_sender_city_id' => 'required_if:boxberry_enabled,true',
            'boxberry_test_mode' => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'delivery');
        }

        return redirect()->back()->with('success', 'Настройки служб доставки обновлены');
    }
} 