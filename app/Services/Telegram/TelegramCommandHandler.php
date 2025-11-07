<?php

namespace App\Services\Telegram;

use App\Models\UserProfile;
use App\Models\Client;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Log;

class TelegramCommandHandler
{
    public function handleStart(array $data): array
    {
        try {
            $telegramId = $data['from']['id'] ?? null;
            $chatId = $data['chat']['id'] ?? null;
            $chat = $data['chat'] ?? [];

            if (!$telegramId || !$chatId) {
                return ['ok' => false, 'message' => 'Invalid command data'];
            }

            $telegraphChat = TelegraphChat::where('chat_id', $chatId)->first();

            if (!$telegraphChat) {
                $telegraphChat = TelegraphChat::create([
                    'chat_id' => $chatId,
                    'name' => $chat['title'] ?? 'Private Chat',
                    'type' => $chat['type'] ?? 'private',
                ]);
            }

            // Проверяем есть ли уже пользователь
            $userProfile = UserProfile::where('telegram_user_id', $telegramId)->first();

            if ($userProfile) {
                // Обновляем chat_id и данные
                $userProfile->update([
                    'telegram_chat_id' => $chatId,
                    'first_name' => $data['from']['first_name'] ?? null,
                    'last_name' => $data['from']['last_name'] ?? null,
                ]);

                $telegraphChat->html(
                    "👋 Добро пожаловать, " .
                    ($userProfile->first_name ?? 'Пользователь') .
                    "! Я рад видеть вас снова."
                )->send();

                return ['ok' => true];
            }

            // Создаём новый профиль и ждем email
            $userProfile = UserProfile::create([
                'telegram_user_id' => $telegramId,
                'telegram_chat_id' => $chatId,
                'first_name' => $data['from']['first_name'] ?? null,
                'last_name' => $data['from']['last_name'] ?? null,
            ]);

            cache()->put("awaiting_email_telegram_{$telegramId}", true, now()->addMinutes(10));

            $telegraphChat->html(
                "👋 Привет! Пожалуйста, отправьте свой email, " .
                "чтобы мы могли найти ваш аккаунт."
            )->send();

            Log::info('TelegramCommandHandler: /start handled', [
                'telegram_id' => $telegramId,
                'profile_id' => $userProfile->id
            ]);

            return ['ok' => true];

        } catch (\Exception $e) {
            Log::error('TelegramCommandHandler: Exception in handleStart', [
                'error' => $e->getMessage()
            ]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function handleOrders(array $data): array
    {
        $telegramId = $data['from']['id'] ?? null;
        $chatId = $data['chat']['id'] ?? null;

        if (!$telegramId || !$chatId) {
            return ['ok' => false];
        }

        $userProfile = UserProfile::where('telegram_user_id', $telegramId)->first();
        $telegraphChat = TelegraphChat::where('chat_id', $chatId)->first();

        if (!$userProfile || !$telegraphChat || !$userProfile->client_id) {
            $telegraphChat?->html("❌ Пожалуйста, сначала выполните команду /start")->send();
            return ['ok' => false];
        }

        // TODO: Реализовать список заказов
        $telegraphChat->html("📦 Ваши заказы:\n(здесь будет список)")->send();

        return ['ok' => true];
    }

    public function handleReset(array $data): array
    {
        $telegramId = $data['from']['id'] ?? null;
        $chatId = $data['chat']['id'] ?? null;

        if (!$telegramId || !$chatId) {
            return ['ok' => false];
        }

        UserProfile::where('telegram_user_id', $telegramId)->update([
            'client_id' => null,
            'telegram_user_id' => null,
            'telegram_chat_id' => null,
        ]);

        $telegraphChat = TelegraphChat::where('chat_id', $chatId)->first();
        $telegraphChat?->html("🔄 Ваши данные были сброшены. Выполните /start для начала.")->send();

        return ['ok' => true];
    }

    public function handleHelp(array $data): array
    {
        $chatId = $data['chat']['id'] ?? null;

        if (!$chatId) {
            return ['ok' => false];
        }

        $telegraphChat = TelegraphChat::where('chat_id', $chatId)->first();

        if ($telegraphChat) {
            $telegraphChat->html(
                "<b>Доступные команды:</b>\n\n" .
                "/start - Начать работу\n" .
                "/orders - Мои заказы\n" .
                "/help - Справка\n" .
                "/reset - Сбросить данные"
            )->send();
        }

        return ['ok' => true];
    }
}
