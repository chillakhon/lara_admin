<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /**
     * Get general settings.
     */
    public function general()
    {
        $settings = Setting::getGroup('general');
        return response()->json([
            'status'   => 'success',
            'settings' => $settings,
        ]);
    }

    /**
     * Update general settings.
     */
    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'company_name'    => 'required|string',
            'company_address' => 'required|string',
            'company_phone'   => 'required|string',
            'company_email'   => 'required|email',
            'default_currency'=> 'required|string|size:3',
            'timezone'        => 'required|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'general');
        }

        return response()->json([ 'status' => 'success', 'message' => 'General settings updated successfully' ]);
    }

    /**
     * Get integration settings.
     */
    public function integrations()
    {
        $settings = Setting::getGroup('integrations');
        return response()->json([
            'status'   => 'success',
            'settings' => $settings,
        ]);
    }

    /**
     * Update integration settings.
     */
    public function updateIntegrations(Request $request)
    {
        $validated = $request->validate([
            'cdek_account'    => 'required|string',
            'cdek_password'   => 'required|string',
            'cdek_test_mode'  => 'boolean',
            // Add other integrations validation rules here
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'integrations');
        }

        return response()->json([ 'status' => 'success', 'message' => 'Integration settings updated successfully' ]);
    }

    /**
     * List API keys for current user.
     */
    public function apiKeys()
    {
        $keys = ApiKey::where('user_id', auth()->id())->get();
        return response()->json([
            'status'  => 'success',
            'apiKeys' => $keys,
        ]);
    }

    /**
     * Create a new API key.
     */
    public function createApiKey(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'permissions' => 'required|array',
        ]);

        $apiKey = ApiKey::create([
            'user_id'     => auth()->id(),
            'name'        => $validated['name'],
            'key'         => Str::random(32),
            'permissions' => $validated['permissions'],
        ]);

        return response()->json([
            'status' => 'success',
            'apiKey' => $apiKey,
        ], 201);
    }

    /**
     * Get notification settings.
     */
    public function notifications()
    {
        $settings = Setting::getGroup('notifications');
        return response()->json([
            'status'   => 'success',
            'settings' => $settings,
        ]);
    }

    /**
     * Update notification settings.
     */
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'email_notifications'    => 'required|array',
            'telegram_notifications' => 'required|array',
            'telegram_bot_token'     => 'nullable|string',
            'telegram_chat_id'       => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'notifications');
        }

        return response()->json([ 'status' => 'success', 'message' => 'Notification settings updated successfully' ]);
    }

    /**
     * Get payment settings.
     */
    public function payment()
    {
        $settings = Setting::getGroup('payment');
        return response()->json([
            'status'   => 'success',
            'settings' => $settings,
        ]);
    }

    /**
     * Update payment settings.
     */
    public function updatePayment(Request $request)
    {
        $validated = $request->validate([
            'yookassa_enabled'      => 'boolean',
            'yookassa_shop_id'      => 'required_if:yookassa_enabled,true',
            'yookassa_secret_key'   => 'required_if:yookassa_enabled,true',
            'yookassa_test_mode'    => 'boolean',

            'yandexpay_enabled'     => 'boolean',
            'yandexpay_merchant_id' => 'required_if:yandexpay_enabled,true',
            'yandexpay_api_key'     => 'required_if:yandexpay_enabled,true',

            'robokassa_enabled'     => 'boolean',
            'robokassa_login'       => 'required_if:robokassa_enabled,true',
            'robokassa_password1'   => 'required_if:robokassa_enabled,true',
            'robokassa_password2'   => 'required_if:robokassa_enabled,true',
            'robokassa_test_mode'   => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'payment');
        }

        return response()->json([ 'status' => 'success', 'message' => 'Payment settings updated successfully' ]);
    }

    /**
     * Get delivery settings.
     */
    public function delivery()
    {
        $settings = Setting::getGroup('delivery');
        return response()->json([
            'status'   => 'success',
            'settings' => $settings,
        ]);
    }

    /**
     * Update delivery settings.
     */
    public function updateDelivery(Request $request)
    {
        $validated = $request->validate([
            'cdek_enabled'            => 'boolean',
            'cdek_account'            => 'required_if:cdek_enabled,true',
            'cdek_password'           => 'required_if:cdek_enabled,true',
            'cdek_test_mode'          => 'boolean',
            'cdek_sender_city_id'     => 'required_if:cdek_enabled,true',

            'russian_post_enabled'    => 'boolean',
            'russian_post_token'      => 'required_if:russian_post_enabled,true',
            'russian_post_login'      => 'required_if:russian_post_enabled,true',
            'russian_post_password'   => 'required_if:russian_post_enabled,true',
            'russian_post_test_mode'  => 'boolean',

            'boxberry_enabled'        => 'boolean',
            'boxberry_token'          => 'required_if:boxberry_enabled,true',
            'boxberry_sender_city_id' => 'required_if:boxberry_enabled,true',
            'boxberry_test_mode'      => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'delivery');
        }

        return response()->json([ 'status' => 'success', 'message' => 'Delivery settings updated successfully' ]);
    }
}
