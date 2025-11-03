<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VKWebhookEvent extends Model
{

    protected $table = 'vk_webhook_events';

    protected $fillable = [
        'event_id',
        'type',
        'data',
        'received_at',
    ];

    protected $casts = [
        'data' => 'array',
        'received_at' => 'datetime',
    ];
}
