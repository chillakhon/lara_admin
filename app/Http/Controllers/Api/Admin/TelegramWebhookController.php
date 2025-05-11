<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Log;

class TelegramWebhookController extends Controller
{
    public function webhook(Request $request)
    {
        Log::info('Telegram webhook', request()->all());
        // $message = $request->input('message');
        // $telegramUserId = $message['from']['id'];
        // $phone = optional($message['contact'])['phone_number'];

        // $userProfile = UserProfile::first();
        // if ($userProfile) {
        //     $userProfile->telegram_user_id = $telegramUserId;
        //     $userProfile->save();
        // }

        // return response()->json(['success' => true]);
    }
}
