<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {

    if (!$user) {
        Log::warning('403: user not authenticated for channel', [
            'conversation_id' => $conversationId
        ]);
        return false;
    }

    return true;

});

Broadcast::channel('admin.notifications', function ($user) {

    if (!$user) {
        return false;
    }

    return true;
});


Broadcast::channel('public.conversation.{conversationId}', function ($user, $conversationId) {

    Log::info('Public conversation channel auth', [
        'conversation_id' => $conversationId,
        'is_authenticated' => (bool)$user,
    ]);

    return true;
});
