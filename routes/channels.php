<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {

    Log::info('Auth attempt for channel', [
        'conversation_id' => $conversationId,
        'user' => $user ? $user->only(['id','email']) : null,
        'is_authenticated' => (bool) $user,
    ]);

    if (!$user) {
        Log::warning('403: user not authenticated for channel', [
            'conversation_id' => $conversationId
        ]);
        return false;
    }

    return true;


});
